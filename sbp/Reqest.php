<?php
//Reqest.php
class Reqest implements IReqest{
    private string $data;
    private string $url;
    private int $lenght;

    public function __construct(string $url, array $data){
        $this->url = $url;
        $this->data = http_build_query($data);
        $this->lenght = strlen($this->data);
    }
    public function doRequest(): array{
        $fullUrl = $this->url . '?' . $this->data;
        //$logData = "Данные запроса: " . $this->data . "\n";
        //error_log($logData, 3, 'payment_processor.log');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);         
        curl_setopt($ch, CURLOPT_POST, true);            
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/x-www-form-urlencoded", 
            "Content-Length: ".$this->lenght
        ]);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        //curl_setopt($ch, CURLOPT_CAINFO, 'C:\SSL\cacert.pem');
        $response = curl_exec($ch);
        $logResponse = "Ответ от сервера: " . $response . "\n";
        error_log($logResponse, 3, 'payment_processor.log');  // Путь к вашему лог-файлу
        if (curl_errno($ch)) {
            echo "cURL Error: " . curl_error($ch);
            curl_close($ch);
            exit;
        }

        curl_close($ch);
        $xmlObject = simplexml_load_string($response);
        $result = Reqest::xmlToArray($xmlObject);

        return $result;
    }

    private function xmlToArray($xml)
    {
        $result = [];
        foreach ($xml->attributes() as $attrName => $attrValue) {
            $result['@attributes'][$attrName] = (string)$attrValue;
        }

        foreach ($xml->children() as $childName => $child) {
            $childArray = Reqest::xmlToArray($child);

            if ($childName == 'parameters') {
                $result[$childName] = [
                    '@attributes' => $childArray['@attributes'], 
                    'parameter' => []  
                ];

                foreach ($child->parameter as $param) {
                    $paramArray = Reqest::xmlToArray($param); 
                    $result[$childName]['parameter'][] = $paramArray;
                }
            } else {
                if (count($child->children()) == 0) {
                    $result[$childName] = (string)$child;
                } 
                else {
                    $result[$childName] = $childArray;
                }
            }
        }

        return $result;
    }
}