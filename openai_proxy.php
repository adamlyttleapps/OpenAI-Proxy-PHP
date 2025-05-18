<?php
// AI Wrapper Proxy Script
// Created by Adam Lyttle

// Security improvements provided by:
// https://github.com/quinncomendant

// Make cool stuff and share your build:
//  --> x.com/adamlyttleapps
//  --> github.com/adamlyttleapps

// === CONFIGURATION ===

// STEP 1: ADD YOUR OPENAI KEY (use environment variable for safety)
$openai_key = getenv("OPENAI_API_KEY");

// STEP 2: SPECIFY THE LOCATION WHERE THIS SCRIPT IS HOSTED
$script_location = "https://yourdomain.com/demo/OpenAIProxy-PHP"; // <-- update this

// STEP 3: CUSTOM SYSTEM PROMPT
$custom_prompt = "You are a friendly chatbot called 'Test Identifier: AI Wrapper Test Agent' whose only purpose is to assist the user with identifying what they have taken a photo of, tips, and other helpful information.";

// STEP 4: SHARED SECRET KEY (optional)
$shared_secret_key = ""; // leave blank to disable

// === MAIN LOGIC STARTS HERE ===

header('Content-Type: text/plain; charset=utf-8');

// Check if messages were sent
if (!isset($_POST['messages']) || empty($_POST['messages'])) {
    http_response_code(400);
    echo "Missing 'messages' parameter.";
    exit();
}

// Shared secret check (optional)
if (!empty($shared_secret_key)) {
    $client_hash = $_POST['hash'] ?? '';
    $expected_hash = hash_hmac('sha256', $_POST['messages'], $shared_secret_key);
    if (!hash_equals($expected_hash, $client_hash)) {
        http_response_code(403);
        echo "Invalid shared secret.";
        exit();
    }
}

class OpenAI {
    private $api_key;

    public function __construct($key) {
        $this->api_key = $key;
    }

    private function secret_key() {
        return "Bearer {$this->api_key}";
    }

    public function request($messages, $max_tokens = 1500) {
        $body = [
            "model" => "gpt-4o",
            "messages" => $messages,
            "max_tokens" => $max_tokens,
            "temperature" => 0.7,
            "top_p" => 1,
            "presence_penalty" => 0.75,
            "frequency_penalty" => 0.75,
            "stream" => false,
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.openai.com/v1/chat/completions",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: ' . $this->secret_key()
            ]
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        return $error ? ['error' => ['message' => $error]] : json_decode($response, true);
    }
}

// Helpers for formatting messages

function add_message($role, $text) {
    return [
        'role' => $role,
        'content' => [['type' => 'text', 'text' => $text]]
    ];
}

function add_message_image_data($role, $base64_image) {
    global $script_location;

    $decoded = base64_decode(urldecode($base64_image), true);
    if ($decoded === false) return null;

    $hash = md5($base64_image);
    $dir = __DIR__ . "/tmp";
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $file_path = "$dir/{$hash}.jpg";
    file_put_contents($file_path, $decoded);

    // Basic validation
    if (!getimagesize($file_path)) {
        unlink($file_path);
        return null;
    }

    return [
        'role' => $role,
        'content' => [[
            'type' => 'image_url',
            'image_url' => ['url' => "$script_location/tmp/{$hash}.jpg"]
        ]]
    ];
}

function parse_messages($raw_messages) {
    global $custom_prompt;

    $parsed = [add_message("system", $custom_prompt)];

    foreach ($raw_messages as $m) {
        if (!empty($m['message'])) {
            $parsed[] = add_message($m['role'], $m['message']);
        }
        if (!empty($m['image'])) {
            $img_message = add_message_image_data($m['role'], $m['image']);
            if ($img_message) $parsed[] = $img_message;
        }
    }

    return $parsed;
}

// Process the messages
$input = json_decode($_POST['messages'], true);
$messages = parse_messages($input);

// Query OpenAI
$openai = new OpenAI($openai_key);
$response = $openai->request($messages);

// Output response
if (isset($response['choices'][0]['message']['content'])) {
    echo $response['choices'][0]['message']['content'];
} elseif (isset($response['error']['message'])) {
    echo "OpenAI Error: " . $response['error']['message'];
} else {
    echo "An unknown error occurred.";
}
?>
