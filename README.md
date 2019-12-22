# UrbanBuz Integration

## Installation

Using composer:

composer require "urbanbuz-v3/api:dev-master"


## Usage

require('vendor/autoload.php');

use UrbanBuz\API\UrbanBuz;


$url = ''; //  Environment URL

$key ='';  //  API Key

$secret='';//  API Secret

  
$ub = new UrbanBuz($url,$key ,$secret);

$customer = $ub->call('POST', 'customer/online/checkin', $headerParams, $urlParams, $postParams);
