<?php

    // AI Wrapper Proxy Script
    // Created by Adam Lyttle on Jul 09 2024

    // Make cool stuff and share your build with me:

    //  --> x.com/adamlyttleapps
    //  --> github.com/adamlyttleapps

    //STEP 1: ADD YOUR OPENAI KEY
    $openai_key = "sk-XXXXXUcxGZXXXXXUQOXDXXXXXkFJQ2AEXXXXXXi29v71RJF";

    //STEP 2: SPECIFY THE LOCATION WHERE THIS SCRIPT IS STORED:
    $script_location = "https://adamlyttleapps.com/demo/OpenAIProxy-PHP";

    //STEP 3: CONFIGURE YOUR CUSTOM PROMPT:
    $custom_prompt = "You are a friendly chatbot called 'Test Idenfitier: AI Wrapper Test Agent' whose only purpose is to assist the user with identifying what they have taken a photo of, tips, and other information which may be helpful";

    //STEP 4: SETUP THE SHARED SECRET KEY (this is the secret key in the client and server, leave blank if you want to bypass this check)
    $shared_secret_key = "";




    //check if the client has provided messages:
    if(!@$_POST['messages']) {
        header('HTTP/1.1 402 Forbidden');
        exit();
    }
    //check that the secret_key hash is correct:
    else if ($shared_secret_key && md5($_POST['messages'].$shared_secret_key) != @$_POST['hash']) {
        print("Incorrect shared_secret_key");
        exit();
    }

    class Openai{
        private function secret_key(){
            global $openai_key;
            return $secret_key = "Bearer $openai_key";
        }
    
        public function request($messages, $max_tokens){ 
    
            $request_body = [
            "messages" => $messages,
            "max_tokens" => $max_tokens,
            "temperature" => 0.7,
            "top_p" => 1,
            "presence_penalty" => 0.75,
            "frequency_penalty"=> 0.75,
            "stream" => false,
            "model" => "gpt-4o", //configure model here
            ];
    
            $postfields = json_encode($request_body);
            $curl = curl_init();
            curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.openai.com/v1/chat/completions",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $postfields,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: ' . $this->secret_key()
            ],
            ]);
    
            $response = curl_exec($curl);
            $err = curl_error($curl);
    
            curl_close($curl);
    
            if ($err) {
                return "Error #:" . $err;
            } else {
                return json_decode($response,true);
            }
    
        }
    
    }

    //parses single message with image_data
    function add_message_image_data($role,$data) {

        global $script_location;

        $data = urldecode($data);
        $hash = md5($data);

        //save the tmp folder
        @mkdir("tmp"); //create temp folder
        @chmod("tmp", 0777); //setup permission of folder
        $f = @fopen("tmp/{$hash}.jpg","w"); //open tmp filename
        @fwrite($f, $data); //write data
        @fclose($f); //close connection

        $item = new stdClass();
        $item->role = $role;

        $content = new stdClass();
        $content->type = "image_url";
        $content->image_url->url = "$script_location/tmp/{$hash}.jpg";

        $item->content[] = $content;

        return $item;
    }
    
    //parses single message with text
    function add_message($role,$text) {
        $item = new stdClass();
        $item->role = $role;

        $content = new stdClass();
        $content->type = "text";
        $content->text = $text;

        $item->content[] = $content;

        return $item;
    }

    //parses all received messages
    function parse_messages($messages) {

        global $custom_prompt;

        $parsedMessages = [add_message("system", "$custom_prompt")];

        $i = 0;
        foreach ($messages as $message) {
            if($message['image']) {
                $parsedMessages[] = add_message_image_data($message['role'], $message['image']);
            }
            if($message['message']) {
                $parsedMessages[] = add_message($message['role'], $message['message']);
            }
        }
        
        return $parsedMessages;

    }




    //this is where the logic starts:

    //process the received messages
    $messages = parse_messages(json_decode(@$_POST['messages'], true));

    //connects to openai and returns result
    $q = New Openai();
    $openai = $q->request($messages, 1500);

    if($openai['choices'][0]['message']['role']=="assistant") {
        $message = $openai['choices'][0]['message']['content'];
        print($message);
    }
    else {
        print("There was an error: {$openai['error']['message']}");
    }

?>
