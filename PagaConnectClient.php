<?php


class PagaConnectClient {

    var $test_server = "https://qa1.mypaga.com"; //"http://localhost:8080"
    var $live_server = "https://www.mypaga.com";


    /**
     * @param string principal
     *            Business public ID from paga
     * @param string credential
     *            Business password from paga
     * @param string redirectUri
     *             Business web site page
     * @param boolean test
     *            flag to set testing or live(true for test,false for live)
     * @param String scope
     *             the operation types eg. MERCHANT_PAYMENT,USER_DETAILS_REQUEST
     * @param String userData
     *              userData eg. FIRST_NAME, LAST_NAME, USERNAME
     */
    function __construct($builder) {
        $this->principal = $builder->principal;
        $this->credential = $builder->credential;
        $this->redirectUri = $builder->redirectUri;
        $this->test = $builder->test;
        $this->scope = $builder->scope;
        $this->userData = $builder->userData;
    }

    public static function builder(){
        return new Builder();
    }


    /**
     * @param string url
     *            Authorization code url 
     * @param string credntial_prefix 
     *            The value could be either Authorization or Bearer  
     *  @param string credntial 
     *            64 byte encoding of both public ID and password with ':' in between them
     */
    public function buildRequest($url, $credential, $data = null) {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => array("content-type: application/json", "Accept: application/json", "Authorization: $credential"),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_VERBOSE => 1,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_TIMEOUT => 120
        )); 
        if ($data != null) {
            $data_string = json_encode($data);
            curl_setopt($curl, CURLOPT_POSTFIELDS,$data_string);
        }
        return $curl;
    }

    /**
     * @param string authorization_code
     *            The code gotten from user's approval of Merchant's access to their Paga account
     * @param string redirect_uri
     *            Where Merchant would like the user to the redirected to after receiving the access token
     * @param boolean scope
     *            List of activities to be performed with the access token
     * @param boolean user_data
     *            List of user data data to be collected
     * @return JSON Object with access token inside
     *
     */
    function getAccessToken($authorization_code){
        $server = ($this->test) ? $this->test_server : $this->live_server;
        $access_token_url = "/paga-webservices/oauth2/token?";
        $credential = "Basic " . base64_encode("$this->principal:$this->credential");

        $url = $server.$access_token_url."grant_type=authorization_code&redirect_uri=".
        $this->redirectUri."&code=".$authorization_code."&scope=".$this->scope."&user_data=".$this->userData;

        $curl = $this->buildRequest($url, $credential);
 
        $response = curl_exec($curl);

        $this->checkCURL($curl);

        return $response;
    }


    /**
     * @param String $access_token
     *              User's access token
     * @param string reference_number
     *            A unique reference number provided by the client to uniquely identify the transaction
     * @param string amount
     *            Amount to charge the user
     * @param string user_id
     *            A unique identifier for merchant's customer 
     * @param string product_code
     *            Optional identifier for the product to be bought 
     * @param string currency
     *            The currency code of the transaction
     * @return JSON Object
     *
     */
    function merchantPayment($access_token, $reference_number, $amount, $user_id, $product_code, $currency){
        
        $server = ($this->test) ? $this->test_server : $this->live_server;
        $merchantPaymentUrl = $server."/paga-webservices/oauth2/secure/merchantPayment";
        $credential = "Bearer ". $access_token;
        $payment_link = "";

        if($currency == null){
            $payment_link = $merchantPaymentUrl."/referenceNumber/".$reference_number."/amount/".
            $amount."/merchantCustomerReference/".$user_id."/merchantProductCode/".$product_code;
        }
        elseif ($currency == null && $product_code ==null){
            $payment_link = $merchantPaymentUrl."/referenceNumber/".$reference_number."/amount/".
            $amount."/merchantCustomerReference/".$user_id;
        }
        else{
            $payment_link = $merchantPaymentUrl."/referenceNumber/".$reference_number."/amount/".
            $amount."/merchantCustomerReference/".$user_id."/merchantProductCode/".
            $product_code."/currency/".$currency;
        }

        $curl = $this->buildRequest($payment_link, $credential);
        $response = curl_exec($curl);

        $this->checkCURL($curl);

        return $response;

    }


     /*
     * @param String $access_token
     *              User's access token
     * @param string reference_number
     *            A unique reference number provided by the client to uniquely identify the transaction
     * @param string amount
     *            Amount to charge the user
     * @param String recipientPhoneNumber
      *            recipientPhoneNumber
     * @param boolean skipMessaging 
     *            Turn off Notification of User about payment made to their account.
     * @return JSON Object
     *
     */
    function moneyTransfer($access_token, $referenceNumber, $amount, $recipientPhoneNumber, $skipMessaging){

        $server = ($this->test) ? $this->test_server : $this->live_server;
        $moneyTransferUrl = $server."/paga-webservices/oauth2/secure/moneyTransfer";
        $credential = "Bearer ". $access_token;
        $transfer_link = "";

        if($skipMessaging != null){
            $transfer_link = $moneyTransferUrl."/amount/".$amount."/destinationPhoneNumber/".$recipientPhoneNumber."/skipMessaging/".$skipMessaging."/referenceNumber/".$referenceNumber;
        }
        else {
            $transfer_link = $moneyTransferUrl."/amount/".$amount."/destinationPhoneNumber/".$recipientPhoneNumber."/referenceNumber/".$referenceNumber;
        }
        
        $curl = $this->buildRequest($transfer_link, $credential);
        $response = curl_exec($curl);

        $this->checkCURL($curl);

        return $response;

    }

    /**
     * @param String $access_token
     *              User's access token
     * @param string reference_number
     *            A unique reference number provided by the client to uniquely identify the transaction
     * @return JSON Object
     *
     */
    function getOneTimeToken($access_token, $reference_number){

        $server = ($this->test) ? $this->test_server : $this->live_server;
        $oneTimeTokenUrl = $server."/paga-webservices/oauth2/secure/getOneTimeToken";
        $credential = "Bearer ". $access_token;
        $oneTimeToken_link = $oneTimeTokenUrl."/referenceNumber/".$reference_number;

        $curl = $this->buildRequest($oneTimeToken_link, $credential);
        $response = curl_exec($curl);

        $this->checkCURL($curl);

        return $response;

    }

    /**
     * @param String $access_token
     *              User's access token
     * @param string $reference_number
     *            A unique reference number provided by the client to uniquely identify the transaction
     * @return string JSON Object
     */
    function getUserDetails($access_token,$reference_number){

        $server = ($this->test) ? $this->test_server : $this->live_server;
        $getUserDetailUrl = $server."/paga-webservices/oauth2/secure/getUserDetails";
        $userDetail_link = $getUserDetailUrl."/referenceNumber/".$reference_number;
        $credential = "Bearer ". $access_token;

        $curl = $this->buildRequest($userDetail_link, $credential);
        $response = curl_exec($curl);

        $this->checkCURL($curl);

        return $response;

    }

    /**
     * @param $curl
     */
    public function checkCURL($curl)
    {
        if (curl_errno($curl)) {
            echo 'Curl error: ' . curl_error($curl);
        }

        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        printf("<br/>HTTP Code: " . $httpcode);

        if ($httpcode == 200) {
            printf("SUCCESSFUL");
        }
        //var_dump($response);

        curl_close($curl);
    }

}

class Builder {
    function __construct() {
        
    }
    public function setPrincipal($principal) {
       $this->principal = $principal;
       return $this;
    }
    public function setCredential($credential){
        $this->credential =$credential;
       return $this;
    }
    public function setRedirectUri($redirect){
        $this->redirectUri = $redirect;
        return $this;
    }
    public function setScope($scopeArr){
        $this->scope = implode("+", $scopeArr);
       return $this;
    }
    public function setUserData($userDataArr){
        $this->userData = implode("+", $userDataArr);
       return $this;
    }
    public function setTest($flag){
        $this->test = $flag;
       return $this;
    }
    public function build() {
       return new PagaConnectClient($this);
    }
 }
 


?>


