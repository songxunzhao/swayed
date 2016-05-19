<?php
//function
function genToken($userid) {
	$tk = \Token::where("user_id", "=", $userid)->first();
	if ($tk == null) {
		$tk = new \Token;
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
	$token = \Token::where("token", "=", $token)->first();
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

//middleware
$app->add(function ($request, $response, $next) {
	$reqPath = $request->getUri()->getPath();
	if ($reqPath == 'v1/user/login' || $reqPath == 'v1/user/signup') {
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

$app->post('/v1/user/signup', function ($request, $response, $args) {
	$data = $request->getParsedBody();
	$name = $data['name'];
	$email = $data['email'];
	$password = $data['password'];
	$user_type = $data['user_type'];

	$resp = array();
	$resp['error'] = "";
	$resp['code'] = 200;
	$resp['data'] = "";
	//valiate
	if (empty($password) || empty($email) || empty($name) || empty($user_type)) {
		$resp['error'] = "Invalid data"; 
		$resp['code'] = 400;
		goto end;
	}
	if ($user_type != "brand" && $user_type != "influencer") {
		$resp['error'] = "Invalid user type"; 
		$resp['code'] = 400;
		goto end;
	}
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$resp['error'] = "Invalid email format"; 
		$resp['code'] = 400;
		goto end;
	}
	if (\User::where('email', '=', $email)->first() != null) {
		$resp['error'] = "Email exist"; 
		$resp['code'] = 400;
		goto end;
	}

	//signup
	$user = new \User;
	$user->email = $email;
	$user->password = md5($password);
	$user->name = $name;
	$user->user_type = $user_type;
	$user->uuid = uniqid();
	$user->save();

	if ($user_type == "brand") {
		$brand = new \Brand;
		$brand->user_id = $user->uuid;
		$brand->save();
	} else {
		$influencer = new \Influencer;
		$influencer->user_id = $user->uuid;
		$influencer->save();
	}

	$resp['data'] = array();
	$resp['data']['token'] =  genToken($user->uuid);
	$resp['data']['profile'] = array();
	$resp['data']['profile']['email'] = $email;
	$resp['data']['profile']['name'] = $name;
	$resp['data']['profile']['user_type'] = $user_type;
	$resp['data']['profile']['location'] = "";
	$resp['data']['profile']['description'] = "";
	$resp['data']['profile']['social_id'] = "";
	$resp['data']['profile']['social_token'] = "";

	end:
    $response->getBody()->write(json_encode($resp));

    return $response;
});

$app->post('/v1/user/login', function ($request, $response, $args) {
	$data = $request->getParsedBody();
	$email = $data['email'];
	$password = $data['password'];

	$resp = array();
	$resp['error'] = "";
	$resp['code'] = 200;
	$resp['data'] = "";
	//valiate
	if (empty($password) || empty($email)) {
		$resp['error'] = "Invalid data"; 
		$resp['code'] = 400;
		goto end;
	}
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$resp['error'] = "Invalid email format"; 
		$resp['code'] = 400;
		goto end;
	}

	$user = \User::where('email', '=', $email)->first();
	if ($user  == null) {
		$resp['error'] = "Invalid login info"; 
		$resp['code'] = 400;
		goto end;
	} else {
		if (md5($password) != $user->password) {
			$resp['error'] = "Invalid login info"; 
			$resp['code'] = 400;
			goto end;
		}
	}

	$resp['data'] = array();
	$resp['data']['token'] =  genToken($user->uuid);
	$resp['data']['profile'] = array();
	$resp['data']['profile']['email'] = $user->email;
	$resp['data']['profile']['name'] = $user->name;
	$resp['data']['profile']['user_type'] = $user->user_type;
	$resp['data']['profile']['location'] = "";
	$resp['data']['profile']['description'] = "";
	$resp['data']['profile']['social_id'] = "";
	$resp['data']['profile']['social_token'] = "";


	end:
    $response->getBody()->write(json_encode($resp));

    return $response;
});


$app->post('/v1/images', function ($request, $response, $args) {
	$userid = $request->getAttribute('userid');
	$resp = array();
	$resp['error'] = "";
	$resp['code'] = 200;
	$resp['data'] = "";
	
	$files = $request->getUploadedFiles();
    if (empty($files['file'])) {
    	$resp['error'] = "Invalid Data";
        $resp['code'] = 400;
		goto end;
    }

    $newfile = $files['file'];
    if ($newfile->getError() === UPLOAD_ERR_OK) {
	    $uploadFileName = explode(".", $newfile->getClientFilename());
	    if (count($uploadFileName) > 1) {
		    $newFileName = $uploadFileName[0] . "-" . uniqid() . "." . $uploadFileName[1];
		    $newfile->moveTo(UPLOAD_PATH . "/" . $newFileName);
		} else {
			$resp['error'] = "The file have not extension";
	        $resp['code'] = 400;
			goto end;
		}
	}

	$resp['data'] = array();
	$resp['data']['token'] = genToken($userid);
	$resp['data']['url'] = BASEURL . "/upload/$newFileName";

	end:
    $response->getBody()->write(json_encode($resp));

    return $response;
});

$app->post('/v1/campaign', function ($request, $response, $args) {
	$userid = $request->getAttribute('userid');
	$data = $request->getParsedBody();
	$main_image = $data['main_image'];
	$objective = $data['objective'];
	$allow_action = $data['allow_action'];
	$ban_action = $data['ban_action'];
	$required_tags = $data['required_tags'];
	$detail_images = $data['detail_images'];


	$resp = array();
	$resp['error'] = "";
	$resp['code'] = 200;
	$resp['data'] = "";

	if (empty($main_image) || empty($objective) || empty($allow_action) || empty($ban_action)) {
		$resp['error'] = "Invalid Data";
		$resp['code'] = 400;
		goto end;
	}

	$campaign = new \Campaign;
	$campaign->main_image = $main_image;
	$campaign->objective = $objective;
	$campaign->allow_action = $allow_action;
	$campaign->ban_action = $ban_action;
	$campaign->uuid = uniqid();
	$campaign->brand_id = $userid;
	$campaign->detail_image = json_encode($detail_images);
	$campaign->save();
	
	$resp['data'] = $campaign->toArray();
	$resp['data']['token'] = genToken($userid);

	end:
    $response->getBody()->write(json_encode($resp));

    return $response;
});


$app->post('/v1/campaign/{camid}', function ($request, $response, $args) {
	$userid = $request->getAttribute('userid');
	$camid = ($request->getAttribute("camid"));

	$data = $request->getParsedBody();
	$main_image = $data['main_image'];
	$objective = $data['objective'];
	$allow_action = $data['allow_action'];
	$ban_action = $data['ban_action'];
	$required_tags = $data['required_tags'];
	$detail_images = $data['detail_images'];


	$resp = array();
	$resp['error'] = "";
	$resp['code'] = 200;
	$resp['data'] = "";

	if (empty($main_image) || empty($objective) || empty($allow_action) || empty($ban_action)) {
		$resp['error'] = "Invalid Data";
		$resp['code'] = 400;
		goto end;
	}
	$campaign = \Campaign::where("uuid", "=", $camid)->first();
	if ($campaign == null) { 
		$resp['error'] = "Campain not found";
		$resp['code'] = 404;
		goto end;
	}
	if ($campaign->brand_id != $userid) { 
		$resp['error'] = "Not your campain";
		$resp['code'] = 400;
		goto end;
	}
	if (!empty($main_image)) {
		$campaign->main_image = $main_image;
	}
	if (!empty($objective)) {
		$campaign->objective = $objective;
	}
	if (!empty($allow_action)) {
		$campaign->allow_action = $allow_action;
	}
	if (!empty($ban_action)) {
		$campaign->ban_action = $ban_action;
	}
	if (!empty($detail_images)) {
		$campaign->detail_image = json_encode($detail_images);
	}
	$campaign->save();
	
	$resp['data'] = $campaign->toArray();
	$resp['data']['token'] = genToken($userid);

	end:
    $response->getBody()->write(json_encode($resp));

    return $response;
});

$app->post('/v1/user/profile/', function ($request, $response, $args) {
	$userid = $request->getAttribute('userid');
	$data = $request->getParsedBody();
	$name = $data['name'];
	$profile_img = $data['profile_img'];
	$social_id = $data['social_id'];
	$social_token = $data['social_token'];
	$description = $data['description'];

	$gender = $data['gender'];
	$country = $data['country'];
	$city = $data['city'];


	$resp = array();
	$resp['error'] = "";
	$resp['code'] = 200;
	$resp['data'] = "";

	
	$user = \User::where("uuid", "=", $userid)->first();
	if (!empty($name)) {
		$user->name = $name;
		$user->save();
	}
	if ($user->user_type == "brand") {
		$profile1 = \Brand::where("user_id", "=", $user->uuid)->first();
		if (!empty($profile_img)) {
			$profile1->profile_img = $profile_img;
		}
		if (!empty($social_token)) {
			$profile1->social_token = $social_token;
		}
		if (!empty($social_id)) {
			$profile1->social_id = $social_id;
		}
		if (!empty($description)) {
			$profile1->description = $description;
		}
		$profile1->save();
		$resp['data'] = $profile1->toArray();
	} else {
		$profile2 = \Influencer::where("user_id", "=", $user->uuid)->first();
		if (!empty($profile_img)) {
			$profile2->profile_img = $profile_img;
		}
		if (!empty($social_token)) {
			$profile2->social_token = $social_token;
		}
		if (!empty($social_id)) {
			$profile2->social_id = $social_id;
		}
		if (!empty($description)) {
			$profile2->description = $description;
		}
		if (!empty($country)) {
			$profile2->country = $country;
		}
		if (!empty($city)) {
			$profile2->city = $city;
		}
		if (!empty($gender)) {
			$profile2->gender = $gender;
		}
		$profile2->save();
		$resp['data'] = $profile2->toArray();
	}
	
	
	$resp['data']['token'] = genToken($userid);

	end:
    $response->getBody()->write(json_encode($resp));

    return $response;
});


$app->get('/', function ($request, $response, $args) {
   	end:
    $response->getBody()->write("I'm a server");

    return $response;
});