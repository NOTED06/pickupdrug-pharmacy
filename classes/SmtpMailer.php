<?php

class SmtpMailer {
    private $host;
    private $port;
    private $email;
    private $password;
    private $encryption;
    private $socket;
    private $from_name;
    
    public function __construct($host, $port, $email, $password, $encryption = 'tls', $from_name = 'PickupDrug') {
        $this->host = $host;
        $this->port = $port;
        $this->email = $email;
        $this->password = $password;
        $this->encryption = $encryption;
        $this->from_name = $from_name;
        $this->socket = null;
    }
    
    
    public function send($to, $subject, $body, $isHtml = true) {
        try {
            
            if (!$this->connect()) {
                error_log("SmtpMailer: Failed to connect to {$this->host}:{$this->port}");
                return false;
            }
            
            
            if (!$this->authenticate()) {
                error_log("SmtpMailer: Authentication failed for {$this->email}");
                return false;
            }
            
            if (!$this->sendEmail($to, $subject, $body, $isHtml)) {
                error_log("SmtpMailer: Failed to send email to {$to}");
                return false;
            }
            
            $this->disconnect();
            error_log("SmtpMailer: Email sent successfully to {$to}");
            return true;
            
        } catch (Exception $e) {
            error_log("SmtpMailer Exception: " . $e->getMessage());
            $this->disconnect();
            return false;
        }
    }
    
    private function connect() {
        $timeout = 10;
        
        
        if ($this->encryption === 'ssl') {
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);
            $this->socket = @stream_socket_client(
                "ssl://{$this->host}:{$this->port}",
                $errno,
                $errstr,
                $timeout,
                STREAM_CLIENT_CONNECT,
                $context
            );
        } else {
            
            $this->socket = @stream_socket_client(
                "tcp://{$this->host}:{$this->port}",
                $errno,
                $errstr,
                $timeout
            );
        }
        
        if (!$this->socket) {
            error_log("SmtpMailer: Connection error - {$errstr} ({$errno})");
            return false;
        }
        
        
        $response = fgets($this->socket, 512);
        if (strpos($response, '220') === false) {
            error_log("SmtpMailer: Invalid greeting - {$response}");
            return false;
        }
        
        
        $this->write("EHLO " . gethostname());
        $response = $this->read();
        
        
        if ($this->encryption === 'tls') {
            $this->write("STARTTLS");
            $response = $this->read();
            
            if (strpos($response, '220') === false) {
                error_log("SmtpMailer: STARTTLS failed - {$response}");
                return false;
            }
            
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                error_log("SmtpMailer: Failed to enable TLS encryption");
                return false;
            }
            
            
            $this->write("EHLO " . gethostname());
            $response = $this->read();
        }
        
        return true;
    }
    
    private function authenticate() {
        
        $this->write("AUTH LOGIN");
        $response = $this->read();
        
        if (strpos($response, '334') === false) {
            error_log("SmtpMailer: AUTH LOGIN not supported - {$response}");
            return false;
        }
        
        
        $this->write(base64_encode($this->email));
        $response = $this->read();
        
        if (strpos($response, '334') === false) {
            error_log("SmtpMailer: Email authentication failed");
            return false;
        }
        
        
        $this->write(base64_encode($this->password));
        $response = $this->read();
        
        if (strpos($response, '235') === false) {
            error_log("SmtpMailer: Password authentication failed - {$response}");
            return false;
        }
        
        return true;
    }
    
    private function sendEmail($to, $subject, $body, $isHtml = true) {
        
        $this->write("MAIL FROM:<{$this->email}>");
        $response = $this->read();
        if (strpos($response, '250') === false) {
            return false;
        }
        
        
        $this->write("RCPT TO:<{$to}>");
        $response = $this->read();
        if (strpos($response, '250') === false) {
            return false;
        }
        
        
        $this->write("DATA");
        $response = $this->read();
        if (strpos($response, '354') === false) {
            return false;
        }
        
        
        $headers = "From: {$this->from_name} <{$this->email}>\r\n";
        $headers .= "To: {$to}\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        
        if ($isHtml) {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        } else {
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        }
        
        $headers .= "Content-Transfer-Encoding: 8bit\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        
        
        $message = $headers . "\r\n" . $body . "\r\n.\r\n";
        fwrite($this->socket, $message);
        
        $response = $this->read();
        return (strpos($response, '250') !== false);
    }
    
    private function write($data) {
        if ($this->socket) {
            fwrite($this->socket, $data . "\r\n");
        }
    }
    
    private function read() {
        $response = '';
        while (!feof($this->socket)) {
            $line = fgets($this->socket, 512);
            $response .= $line;
            
            if (strlen($line) >= 4 && $line[3] !== '-') {
                break;
            }
        }
        return $response;
    }
    
    private function disconnect() {
        if ($this->socket) {
            @fwrite($this->socket, "QUIT\r\n");
            @fclose($this->socket);
            $this->socket = null;
        }
    }
    
    public function __destruct() {
        $this->disconnect();
    }
}
