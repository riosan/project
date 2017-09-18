<?php




trait Log {

    private $logFileName;

    private function writeLog($message)
    {
        $this->logFileName = date("Y-m-d").'.log';
        file_put_contents($this->logFileName,date("Y-m-d H:i:s")." :". $message .PHP_EOL,FILE_APPEND);
    }
}



class Parse {
    private $gates = [];
    private $filename = "ipadress.txt";
    private $pattern = '/^([\d]{0,3}.){1,4}:[\d]{1,5}/';
    private $curl_agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12';
    use Log;

    public function __construct($gates = [])
    {
        if(empty($gates)) exit;
        $this->gates = $gates;
        $this->action();
    }


    private function action()
    {
        foreach ($this->gates as $gate)
        {
            $response = $this->request($gate);
            if(!$response) continue;
            $result = $this->parseInfo($response);
            $this->recordFile($result);
        }

    }

    private function request($gate)
    {
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_USERAGENT,$this->curl_agent);
        curl_setopt ($ch, CURLOPT_URL, $gate);
        curl_setopt ($ch, CURLOPT_POST, 1);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec ($ch);
        if($response === false)
        {
            $this->writeLog(curl_errno($ch).' '.curl_error($ch));
        }
        curl_close($ch);
        return $response;
    }

    private function parseInfo($response)
    {
        $array = explode(PHP_EOL,$response);

        foreach ( $array as $key => $ip)
        {
            if(!preg_match($this->pattern,$ip)){
                unset($array[$key]);
            }
        }

        return $array;

    }

    private function recordFile($new_records)
    {
        if(file_exists($this->filename))
        {

            $handle =  fopen($this->filename,"r");

            $contents = fread($handle, filesize($this->filename));

            $old_record = $this->parseInfo($contents);

            $difference =  $this->match($new_records,$old_record);

            fclose($handle);

            if(count($difference) > 0)
            {
                $this->fileWrite($difference,"a+");
            }

        } else {

                $this->fileWrite($new_records,"w+");
        }

    }

    private function fileWrite($records,$mode)
    {
        $handle =  fopen($this->filename,$mode);

        foreach ($records as $record)
        {
            fwrite($handle,$record.PHP_EOL);
        }
        fclose($handle);
    }

    private function match($new_records,$old_record)
    {
        return array_diff($new_records,$old_record);
    }
}


$gates = ['https://api.good-proxies.ru/get.php?type%5Bsocks4%5D=on&type%5Bsocks5%5D=on&count=0&ping=5000&time=600&key=333dc88edb0481e924b8b41deb945b3e',
          'https://api.good-proxies.ru/get.php?type%5Bhttp%5D=on&count=0&ping=5000&time=600&key=333dc88edb0481e924b8b41deb945b3e'
];


$parse = new Parse($gates);



