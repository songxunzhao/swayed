<?php
use App\Controller\Controller;
use App\Controller\CampaignContractController;
use App\Model\Token;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Capsule\Manager as DB;


//function
function genToken($userid) {
	$tk = Token::where("user_id", "=", $userid)->first();
	if ($tk == null) {
		$tk = new Token;
		$token = sha1(uniqid());
		$tk->user_id = $userid;
		$tk->token = $token;
		$tk->used_at = time();
		$tk ->save();
	} else {
		if (time() - $tk->used_at > 86400) {
			//expired
			$token = sha1(uniqid());
			$tk->token = $token;
			$tk->used_at = time();
			$tk ->save();
		} else {
			$token = $tk->token;
		}
	}
	
	return $token;
}

function validateToken($token) {
	$token = Token::where("token", "=", $token)->first();
	if ($token == null)
		return "";
	else {
		if (time() - $token->used_at > 86400) {
			return "";
		} else {
			return $token->user_id;
		}
	}
}

//Middleware
$app->add(function ($request, $response, $next) {
	$reqPath = $request->getUri()->getPath();

    // Global setting
    Paginator::currentPathResolver(function()
    {
        $uri = $this->request->getUri();
        $reqParams = $this->request->getQueryParams();

        if(isset($reqParams['page']))
            unset($reqParams['page']);

        $reqPath = $uri->getBaseUrl() . '/' . $uri->getPath().'?' . http_build_query($reqParams);
        return $reqPath;
    });

    Paginator::currentPageResolver(function()
    {
        $params = $this->request->getQueryParams();
        if($params['page'])
          return $params['page'];
        else
          return 1;
    });

	if ($reqPath == 'v1/user/login' || $reqPath == 'v1/user/signup' || $reqPath == 'v1/contact_requests') {
		$response = $next($request, $response);
	} else {
		//validate token
		$reqToken = $request->getHeader('Authorization');
		$userid = validateToken($reqToken[0]);
		if (empty($userid)) {
			$resp = array();
			$resp['error'] = "Invalid Auth";
			$resp['code'] = 401;
			$response->getBody()->write(json_encode($resp));
		} else {
			$request = $request->withAttribute('userid', $userid);
			$response = $next($request, $response);
		}
	}

	return $response;
});

// Routes

$app->post('/v1/user/signup', 'App\Controller\Controller:signupUser');

$app->post('/v1/user/login', 'App\Controller\Controller:loginUser');


$app->post('/v1/images', 'App\Controller\Controller:uploadImage');

$app->post('/v1/campaigns', 'App\Controller\Controller:createCampaign');

$app->get('/v1/contact_requests/{reqid}', 'App\Controller\Controller:getContactRequest');


$app->post('/v1/contact_requests', 'App\Controller\Controller:createContactRequest');



$app->put('/v1/campaigns/ca{camid}', 'App\Controller\Controller:updateCampaign');


$app->get('/v1/campaigns/{camid}', 'App\Controller\Controller:getCampaign');

$app->post('/v1/campaigns/list', 'App\Controller\Controller:getCampaignList');

$app->get('/v1/brand/campaigns', 'App\Controller\Controller:getBrandCampaignList');

$app->get('/v1/influencer/campaigns', 'App\Controller\Controller:getInfluencerCampaignContract');


$app->post('/v1/user/profile/', 'App\Controller\Controller:getProfile');


$app->post('/v1/user/rate', 'App\Controller\Controller:updateUserRate');

$app->post('/v1/user/interests', 'App\Controller\Controller:updateUserInterests');

$app->get('/v1/interest_tags', 'App\Controller\Controller:getInterestTags');

$app->post('/v1/campaigns/{camid}/apply', 'App\Controller\Controller:applyForCampaign');

$app->post('/v1/campaigns/{camid}/influencers/{influid}/offer', 'App\Controller\Controller:offerCampaign');

$app->get('/v1/campaigns/{camid}/influencers', 'App\Controller\Controller:getContractsForCampaign');

$app->get('/v1/campaigns/{camid}/cancel', 'App\Controller\Controller:cancelCampaign');

$app->get('/v1/campaign_contracts/{contract_id}/{action:award|decline|accept|reject}',
    'App\Controller\CampaignContractController:get');

$app->post('/v1/influencers/list', 'App\Controller\Controller:searchInfluencerList');

$app->get('/v1/faqs', 'App\Controller\Controller:getFaqList');

$app->get('/', function ($request, $response, $args) {
   	end:
    $response->getBody()->write("I'm a server");

    return $response;
});