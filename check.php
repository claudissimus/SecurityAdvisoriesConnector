<?php
/**
 * Simple Tool for using the SensioLabs "Security Advisories Checker"
 * 
 * (c) 2016 Claude Schmidhuber
 */         
  
/**
 * Check a composer.lock file via API using cURL
 * @author Claude Schmidhuber
 */ 
class SensioSecurityConnector
{
    const URL = "https://security.sensiolabs.org/check_lock";
    
    protected $message = "";
    protected $headers;
    protected $count = 0;
    protected $plainText = true;
    
    protected function getHeaders($headerText)
    {
        $h = array();
        foreach (explode("\r\n", $headerText) as $line)
        {
            list ($key, $value) = explode(':', $line);
            $h[$key] = trim($value);
        }        
        return $h;
    }
    
    protected function request($file)
    {
         $headers = array("Content-Type:multipart/form-data");
         if ($this->plainText) 
            $headers[]= "Accept: text/plain";
         else
            $headers[]= "Accept: text/html";
         $postfields = array("lock" => "@".$file, "filename" => basename($file));
         $options = array(
            CURLOPT_URL => self::URL,
            CURLOPT_HEADER => true,
            CURLOPT_POST => 1,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $postfields,
            CURLOPT_INFILESIZE => filesize($file),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false // should install needed certs locally and set this to true to be secure
        );     
        $c = curl_init();
        curl_setopt_array($c, $options);
        $response = curl_exec($c);
        if (curl_errno($c) != 0)
        {
            throw new Exception("cURL Error: ".curl_error($c));
        }
        $info = curl_getinfo($c);
        if ($info["http_code"] != 200)
        {
            throw new Exception("Server responsed with ".$info["http_code"]);
        }
        if (gettype($c) == 'resource') curl_close($c); // move to finally{} with php5.5
        $this->message = substr($response, $info["header_size"]);
        $this->headers = $this->getHeaders(substr($response, 0, $info["header_size"]));
        $this->count = $this->headers["X-Alerts"];        
        return $this->count;
    } 

    public function hasIssues($file = "composer.lock")
    {
        return ( $this->request($file) > 0 );
    }
    
    public function getReport()
    {
        return $this->message;
    }  
    
    public function getCount()
    {
        return $this->count;
    }
    
    public function setPlainText($pt)
    {
        $this->plainText = $pt;
    }   
}    
    
echo "'SensioLabs Security Advisories Checker' Tool v0.1\n";
echo "--------------------------------------------------\n";
    
$F = "composer.lock";
if ($argc >= 2)
{
    $F = $argv[1];
}    

if (!file_exists($F))
{
    echo "Syntax: php check.php <path to composer.lock>\n";
    echo "If <path> is omitted, local directory is used.\n";
    echo "\n";
    echo "ERROR: File ".$F." does not exist.\n";
    exit(-1);
}       

try
{
  $S = new SensioSecurityConnector();
  
  if ($S->hasIssues($F))
  {
    echo $S->getReport();
    exit ($S->getCount());
  }  
  else
  {
    echo "No issues.";
    exit(0);
  }
}
catch (Exception $ex)
{
  echo "ERROR: ".$ex->getMessage()."\n";
  exit(-2);  
}

