<?php
require __DIR__ . '/../src/controller.php';
use Model\Token;
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

$app->post('/v1/user/signup', 'App\Controller:signupUser');

$app->post('/v1/user/login', 'App\Controller:loginUser');


$app->post('/v1/images', 'App\Controller:uploadImage');

$app->post('/v1/campaigns', 'App\Controller:createCampaign');

$app->get('/v1/contact_requests/{reqid}', 'App\Controller:getContactRequest');


$app->post('/v1/contact_requests', 'App\Controller:createContactRequest');



$app->put('/v1/campaigns/ca{camid}', 'App\Controller:updateCampaign');


$app->get('/v1/campaigns/{camid}', 'App\Controller:getCampaign');

$app->post('/v1/campaigns/list', 'App\Controller:getCampaignList');

$app->get('/v1/brand/campaigns', 'App\Controller:getBrandCampaignList');

$app->get('/v1/influencer/campaigns', 'App\Controller:getInfluencerCampaignContract');


$app->post('/v1/user/profile/', 'App\Controller:getProfile');


$app->post('/v1/user/rate', 'App\Controller:updateUserRate');

$app->post('/v1/user/interests', 'App\Controller:updateUserInterests');

$app->get('/v1/interest_tags', 'App\Controller:getInterestTags');

$app->post('/v1/campaigns/{camid}/apply', 'App\Controller:applyForCampaign');

$app->post('/v1/campaigns/{camid}/influencers/{influid}/offer', 'App\Controller:offerCampaign');


$app->get('/v1/campaigns/{camid}/influencers', 'App\Controller:getContractsForCampaign');

$app->get('/v1/campaigns/{camid}/cancel', 'App\Controller:cancelCampaign');


$app->post('/v1/influencers/list', 'App\Controller:searchInfluencerList');

$app->get('/v1/faqs', 'Controller:getFaqList');

$app->get('/', function ($request, $response, $args) {
   	end:
    $response->getBody()->write("I'm a server");

    return $response;
});