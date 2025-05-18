# OpenAI-Proxy-PHP

A simple PHP script designed to protect your OpenAI API key and ensure secure communication.

## Overview

OpenAI-Proxy-PHP is a PHP script that secures your OpenAI API key, preventing it from being exposed in your application code or transmitted in plaintext over the network. This script is utilized in the AIWrapper-SwiftUI repository to enhance security.

## Features

* API Key Protection: Safeguard your OpenAI API key from exposure.
* Secure Communication: Prevents the API key from being transmitted in plaintext.
* Customizable Prompts: Easily configure custom prompts for your AI.
* Shared Secret Key: Optional shared secret key for an additional layer of security.

This script is used in the AIWrapper-SwiftUI repo located at 
[https://github.com/adamlyttleapps/OpenAI-Wrapper-SwiftUI](https://github.com/adamlyttleapps/OpenAI-Wrapper-SwiftUI)

## Usage:

To use OpenAI-Proxy-PHP, follow these steps:

Copy openai_proxy.php onto your server and then follow the instructions withint the source code:

1.	**Copy the Script:** Copy openai_proxy.php to your server.
2.	**Configure the Script:** Open the openai_proxy.php file and follow these instructions:

```
// STEP 1: ADD YOUR OPENAI KEY
For Apache (.htaccess):
SetEnv OPENAI_API_KEY sk-xxxxxx...

For Nginx + PHP-FPM (conf):
fastcgi_param OPENAI_API_KEY sk-xxxxxx...;

Or in php.ini:
env[OPENAI_API_KEY] = "sk-xxxxxx..."

// STEP 2: SPECIFY THE LOCATION WHERE THIS SCRIPT IS STORED
$script_location = "https://adamlyttleapps.com/demo/OpenAIProxy-PHP";

// STEP 3: CONFIGURE YOUR CUSTOM PROMPT
$custom_prompt = "You are a friendly chatbot called 'Test Identifier: AI Wrapper Test Agent' whose only purpose is to assist the user with identifying what they have taken a photo of, tips, and other information which may be helpful";

// STEP 4: SETUP THE SHARED SECRET KEY
// (this is the secret key in the client and server, leave blank if you want to bypass this check)
$shared_secret_key = "";
```

## Shared Secret Key

When the $shared_secret_key is set, the script expects each client request to include a secure HMAC hash. The server verifies the incoming request by computing:
```
hash_hmac('sha256', $_POST['messages'], $shared_secret_key)
```
It then compares the result against the client-provided $_POST['hash'] using hash_equals() to prevent timing attacks.

This allows you to control access by rotating or updating the shared secret. If the hash is missing or incorrect, the request will be rejected with a 403 response.

## Special Thanks

Special thanks to [quinncomendant](https://github.com/quinncomendant) for providing important security recommendations

## Contributions

Contributions are welcome! Feel free to open an issue or submit a pull request on the [GitHub repository](https://github.com/adamlyttleapps/OpenAI-Proxy-PHP).

## MIT License

This project is licensed under the MIT License. See the LICENSE file for more details.

This README provides a clear overview of the project, detailed usage instructions, and additional sections like examples, contributions, and licensing, making it more comprehensive and user-friendly.
