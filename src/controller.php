<?php
/**
 * Created by PhpStorm.
 * User: songxun
 * Date: 7/22/2016
 * Time: 1:19 AM
 */

class Controller {
    public function signupUser($request, $response, $args) {
        $data = $request->getParsedBody();
        $email = $data['email'];
        $password = $data['password'];
        $user_type = $data['user_type'];

        $resp = array();
        $resp['error'] = "";
        $resp['code'] = 200;
        $resp['data'] = "";
        //Validate
        if (empty($password) || empty($email) || empty($user_type)) {
            $resp['error'] = "Some fields are missing or wrong";
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
        if (User::where('email', '=', $email)->first() != null) {
            $resp['error'] = "Email exist";
            $resp['code'] = 400;
            goto end;
        }

        //signup
        $user = new User;
        $user->email = $email;
        $user->name = '';
        $user->password = md5($password);
        $user->user_type = $user_type;
        $user->uuid = uniqid();
        $user->save();

        if ($user_type == "brand") {
            $brand = new Brand;
            $brand->user_id = $user->uuid;
            $brand->save();
        } else {
            $influencer = new Influencer;
            $influencer->user_id = $user->uuid;
            $influencer->save();
        }

        $resp['data'] = array();
        $resp['data']['token'] =  genToken($user->uuid);
        $resp['data']['profile'] = $user->toProfileArray();

        end:
        $response->getBody()->write(json_encode($resp));

        return $response;
    }

    public function loginUser($request, $response, $args) {
        $data = $request->getParsedBody();
        $email = $data['email'];
        $password = $data['password'];

        $resp = array();
        $resp['error'] = "";
        $resp['code'] = 200;
        //valiate
        if (empty($password) || empty($email)) {
            $resp['error'] = "Some fields are missing or wrong";
            $resp['code'] = 400;
            goto end;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $resp['error'] = "Invalid email format";
            $resp['code'] = 400;
            goto end;
        }

        $user = User::where('email', '=', $email)->first();
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
        $resp['data']['token'] = genToken($user->uuid);
        $resp['data']['profile'] = $user->toProfileArray();

        $tags = UserInterest::where("user_id", "=", $user->uuid)->get();
        $tagName = array();
        foreach ($tags as $item) {
            $tagName[] = $item->tag;
        }
        $resp['data']['profile']['interests'] = $tagName;

        end:
        $response->getBody()->write(json_encode($resp));

        return $response;
    }

    function uploadImage($request, $response, $args) {
        $userid = $request->getAttribute('userid');
        $resp = array();
        $resp['error'] = "";
        $resp['code'] = 200;
        $resp['data'] = "";

        $files = $request->getUploadedFiles();
        if (empty($files['file'])) {
            $resp['error'] = "Some fields are missing or wrong";
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

        $resp['data']['url'] = BASEURL . "/upload/$newFileName";

        end:
        $response->getBody()->write(json_encode($resp));

        return $response;
    }

    function createCampaign($request, $response, $args) {
        $user_id = $request->getAttribute('userid');
        $user = User::where("uuid", "=", $user_id)->first();

        $data = $request->getParsedBody();
        $main_image = $data['main_image'];
        $objective = $data['objective'];
        $allow_action = $data['allow_action'];
        $ban_action = $data['ban_action'];
        $required_tags = $data['required_tags'];
        $detail_images = $data['detail_images'];
        $name = $data['name'];


        $resp = array();
        $resp['error'] = "";
        $resp['code'] = 200;
        $resp['data'] = "";

        if ($user->user_type == "influencer") {
            $resp['error'] = "Only brand can create campaigns";
            $resp['code'] = 400;
            goto end;
        }
        if (empty($main_image) || empty($objective) || empty($allow_action) || empty($ban_action) || empty($name)) {
            $resp['error'] = "Some fields are missing or wrong";
            $resp['code'] = 400;
            goto end;
        }

        $campaign = new Campaign;
        $campaign->main_image = $main_image;
        $campaign->objective = $objective;
        $campaign->allow_action = $allow_action;
        $campaign->ban_action = $ban_action;
        $campaign->uuid = 'ca'. uniqid();
        $campaign->brand_id = $user_id;
        $campaign->name = $name;
        $campaign->status = 1;
        $campaign->detail_images = json_encode($detail_images);
        if (!empty($required_tags)) {
            $campaign->hashtags = json_encode($required_tags);
        }
        $campaign->save();

        // Copy brand's interest tags from campaign
        $interests = UserInterest::where('user_id','=', $user_id)->get();
        foreach($interests as $interest) {
            $ca_interest = new CampaignInterest;
            $ca_interest->campaign_id = $campaign->uuid;
            $ca_interest->uuid = 'ci' . uniqid();
            $ca_interest->tag  = $interest->tag;
            $ca_interest->save();
        }

        $resp['data'] = $campaign->toArray();

        end:
        $response->getBody()->write(json_encode($resp));

        return $response;
    }

    function getContactRequest($request, $response, $args) {
        $userid = $request->getAttribute('userid');
        $reqid = ($request->getAttribute("reqid"));

        $user = User::where("uuid", "=", $userid)->first();

        $resp = array();
        $resp['error'] = "";
        $resp['code'] = 200;
        $resp['data'] = "";

        if (empty($reqid)) {
            $resp['error'] = "Some fields are missing or wrong";
            $resp['code'] = 400;
            goto end;
        }

        $campaign = ContactRequest::where('uuid', '=', $reqid)->first();
        if ($campaign == null) {
            $resp['error'] = "Contact request not found";
            $resp['code'] = 404;
            goto end;
        }


        $resp['data'] = $campaign->toArray();

        end:
        $response->getBody()->write(json_encode($resp));

        return $response;
    }

    function createContactRequest($request, $response, $args) {
        $userid = $request->getAttribute('userid');
        $user = User::where("uuid", "=", $userid)->first();

        $data = $request->getParsedBody();
        $first_name = $data['first_name'];
        $last_name = $data['last_name'];
        $email = $data['email'];
        $phone = $data['phone'];
        $message = $data['message'];


        $resp = array();
        $resp['error'] = "";
        $resp['code'] = 200;
        $resp['data'] = "";

        if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($message)) {
            $resp['error'] = "Some fields are missing or wrong";
            $resp['code'] = 400;
            goto end;
        }

        $contact_request = new ContactRequest;
        $contact_request->first_name = $first_name;
        $contact_request->last_name = $last_name;
        $contact_request->email = $email;
        $contact_request->phone = $phone;
        $contact_request->message = $message;
        $contact_request->status = 'open';
        $contact_request->uuid = uniqid();
        $contact_request->save();

        $resp['data'] = array();;
        $resp['data']['message_id'] = $contact_request->uuid;
        $resp['data']['message'] = 'Contact request was sent. Our operator will contact you shortly';

        end:
        $response->getBody()->write(json_encode($resp));

        return $response;
    }

    function updateCampaign($request, $response, $args) {
        $userid = $request->getAttribute('userid');
        $camid = 'ca'.($request->getAttribute("camid"));
        $user = User::where("uuid", "=", $userid)->first();

        $data = $request->getParsedBody();

        $resp = array();
        $resp['error'] = "";
        $resp['code'] = 200;
        $resp['data'] = "";

        if ($user->user_type == "influencer") {
            $resp['error'] = "Only brand can do that";
            $resp['code'] = 400;
            goto end;
        }

        if (empty($data['main_image']) || empty($data['objective'])
            || empty($data['allow_action']) || empty($data['ban_action']))
        {
            $resp['error'] = "Some fields are missing or wrong";
            $resp['code'] = 400;
            goto end;
        }
        $campaign = Campaign::where("uuid", "=", $camid)->first();
        if ($campaign == null) {
            $resp['error'] = "Campaign not found";
            $resp['code'] = 404;
            goto end;
        }
        if ($campaign->brand_id != $userid) {
            $resp['error'] = "Not your campaign";
            $resp['code'] = 400;
            goto end;
        }

        if (!empty($data['detail_images'])) {
            $data['detail_images'] = json_encode($data['detail_images']);
        }
        if (!empty($data['hashtags'])) {
            $data['hashtags'] = json_encode($data['hashtags']);
        }
        $campaign->update($data);

        $resp['data'] = $campaign->toArray();

        end:
        $response->getBody()->write(json_encode($resp));

        return $response;
    }

    function getCampaign($request, $response, $args) {
        $userid = $request->getAttribute('userid');
        $camid = ($request->getAttribute("camid"));
        $user = User::where("uuid", "=", $userid)->first();

        $resp = array();
        $resp['error'] = "";
        $resp['code'] = 200;
        $resp['data'] = "";


        $campaign = Campaign::where("uuid", "=", $camid)->first();
        if ($campaign == null) {
            $resp['error'] = "Campaign not found";
            $resp['code'] = 404;
            goto end;
        }

        $resp['data'] = $campaign->toArray();

        end:
        $response->getBody()->write(json_encode($resp));

        return $response;
    }

    function getCampaignList($request, $response, $args) {
        $userid = $request->getAttribute('userid');
        $user = User::where("uuid", "=", $userid)->first();

        $page_size = isset($_GET['page_size']) ? $_GET['page_size'] : 20;

        $data = $request->getParsedBody();

        $resp = array();
        $resp['error'] = "";
        $resp['code'] = 200;
        $resp['data'] = "";


        $query = Campaign::where('status', '<>', 3)->orderBy("created_at", "DESC");

        if (!empty($data['tags'])) {
            $tag = CampaignInterest::whereIn("tag", $data['tags'])->get();
            $uuid = array();
            foreach ($tag as $t) {
                $uuid[] = $t->campaign_id;
            }
            $query = $query->whereIn("uuid", $uuid);
        }

        if (!empty($data['search'])) {
            $query = $query->where("name", "LIKE", "%" . $data['search'] . "%");
        }

        $campaigns = $query->paginate($page_size);
        $resp['data']['results'] = $campaigns->getCollection()->toArray();
        $resp['data']['next'] = $campaigns->nextPageUrl();
        $resp['data']['prev'] = $campaigns->previousPageUrl();
        $resp['data']['count'] = $campaigns->total();

        $response->getBody()->write(json_encode($resp));

        return $response;
    }

    function getBrandCampaignList($request, $response, $args) {
        $userid = $request->getAttribute('userid');
        $user = User::where("uuid", "=", $userid)->first();

        $page_size = isset($_GET['page_size']) ? $_GET['page_size'] : 20;
        $status = isset($_GET['status']) ? $_GET['status'] : 0;

        $data = $request->getParsedBody();


        $resp = array();
        $resp['error'] = "";
        $resp['code'] = 200;
        $resp['data'] = "";

        if ($user->user_type == "influencer") {
            $resp['error'] = "Only brand can do that";
            $resp['code'] = 400;
            goto end;
        }

        $campaign_list = Campaign::where("brand_id", "=", $userid);
        if ($status > 0) {
            $campaign_list = $campaign_list->where("status", "=", $status);
        }
        $campaign_list = $campaign_list->orderBy("status", "ASC")->orderBy("created_at", "DESC")->paginate($page_size);
        foreach($campaign_list as &$campaign) {
            $campaign->num_influencers = CampaignContract::where('campaign_id', $campaign->uuid)->count();
        }

        $resp['data']['results'] = $campaign_list->getCollection()->toArray();
        $resp['data']['count'] = $campaign_list->total();
        $resp['data']['next'] = $campaign_list->nextPageUrl();
        $resp['data']['prev'] = $campaign_list->previousPageUrl();

        end:
        $response->getBody()->write(json_encode($resp));

        return $response;
    }

    function getInfluencerCampaignContract($request, $response, $args) {
        $user_id = $request->getAttribute('userid');
        $page_size = isset($_GET['page_size']) ? $_GET['page_size'] : 20;
        $status = isset($_GET['status']) ? $_GET['status'] : 0;

        $resp = array();
        $resp['error'] = "";
        $resp['code'] = 200;
        $resp['data'] = "";

        $user = User::where("uuid", "=", $user_id)->first();
        if($user->user_type != "influencer") {
            $resp['error'] = "You must be influencer";
            $resp['code'] = 403;
            goto end;
        }

        $contract_list = CampaignContract::where('influencer_id', $user->uuid);
        if($status > 0) {
            $contract_list = $contract_list->where('status', '=', $status);
        }
        $contract_list = $contract_list->orderBy("status", "ASC")->paginate($page_size);

        $resp['data']['results'] = $contract_list->getCollection()->toArray();
        $resp['data']['count'] = $contract_list->total();
        $resp['data']['next'] = $contract_list->nextPageUrl();
        $resp['data']['prev'] = $contract_list->previousPageUrl();

        end:
        $response->getBody()->write(json_encode($resp));
        return $response;
    }

    function getProfile($request, $response, $args) {
        $userid = $request->getAttribute('userid');
        $data = $request->getParsedBody();
        $profile_img = $data['profile_img'];
        $description = $data['description'];
        $website = $data['website'];
        isset($data['reach_num']) ? $data['reach_num'] = intval($data['reach_num']) : False;
        isset($data['num_photos']) ? $data['num_photos'] = intval($data['num_photos']) : False;
        $gender = $data['gender'];
        $country = $data['country'];
        $city = $data['city'];


        $resp = array();
        $resp['error'] = "";
        $resp['code'] = 200;
        $resp['data'] = "";


        $user = User::where("uuid", "=", $userid)->first();
        $user->update($data);
        $user->save();

        if ($user->user_type == "brand") {
            $profile = Brand::firstOrNew(['user_id'=>$userid]);
            $data['user_id'] = $userid;
            if(isset($data['user_id']))
                unset($data['user_id']);
            $profile->update($data);
            $profile->save();
        } else {
            $profile = Influencer::firstOrNew(['user_id'=>$userid]);
            $data['user_id'] = $userid;
            $profile->update($data);
            $profile->save();
        }
        end:
        $resp['data'] = $user->toProfileArray();
        $response->getBody()->write(json_encode($resp));

        return $response;
    }

    function updateUserRate($request, $response, $args) {
        $userid = $request->getAttribute('userid');

        $data = $request->getParsedBody();
        $rate = $data['rate'];

        $resp = array();
        $resp['error'] = "";
        $resp['code'] = 200;
        $resp['data'] = "";

        if (empty($rate)) {
            $resp['error'] = "Some fields are missing or wrong";
            $resp['code'] = 400;
            goto end;
        }
        $user = User::where("uuid", "=", $userid)->first();
        if ($user->user_type == "influencer") {
            $influ = Influencer::where("user_id", "=", $userid)->first();
            $influ->rate = $rate;
            $resp['data']['rate'] = $rate;
            $influ->save();
        } else {
            $resp['error'] = "Only for influencer";
            $resp['code'] = 400;
            goto end;
        }

        end:
        $response->getBody()->write(json_encode($resp));

        return $response;
    }

    function updateUserInterests($request, $response, $args) {
        $userid = $request->getAttribute('userid');

        $data = $request->getParsedBody();


        $resp = array();
        $resp['error'] = "";
        $resp['code'] = 200;
        $resp['data'] = "";

        if(!array_key_exists('tags', $data))
            $data = [];
        else
            $data = $data['tags'];

        if (empty($data)) {
            $resp['error'] = "Some fields are missing or wrong";
            $resp['code'] = 400;
            goto end;
        }
        $user = User::where("uuid", "=", $userid)->first();
        UserInterest::where('user_id', '=', $userid)->delete();
        for ($i = 0; $i < count($data); $i++) {
            $tag = InterestTag::where('name', '=', $data[$i])->first();
            if ($tag == null) {
                $tag = new InterestTag;
                $tag->name = $data[$i];
                $tag->save();
            }
            $useri = new UserInterest;
            $useri->uuid = uniqid();
            $useri->user_id = $userid;
            $useri->tag = $data[$i];
            $useri->save();
        }

        $resp['data'] = $data;
        end:
        $response->getBody()->write(json_encode($resp));

        return $response;
    }

    function getInterestTags($request, $response, $args) {
        $userid = $request->getAttribute('userid');

        $resp = array();
        $resp['error'] = "";
        $resp['code'] = 200;

        $tags = InterestTag::all();
        $tagName = array();
        foreach ($tags as $item) {
            $tagName[] = $item->name;
        }
        $resp['data'] = $tagName;

        end:
        $response->getBody()->write(json_encode($resp));

        return $response;
    }

    function applyForCampaign($request, $response, $args) {
        $userid = $request->getAttribute('userid');
        $camid = ($request->getAttribute("camid"));

        $resp = array();
        $resp['error'] = "";
        $resp['code'] = 200;
        $resp['data'] = "";

        if (empty($camid)) {
            $resp['error'] = "Some fields are missing or wrong";
            $resp['code'] = 400;
            goto end;
        }

        $user = User::where("uuid", "=", $userid)->first();

        if ($user->user_type == "brand") {
            $resp['error'] = "Only influencer can do that";
            $resp['code'] = 400;
            goto end;
        }

        $campaignContract = CampaignContract::where("influencer_id", "=", $userid)->where("campaign_id", "=", $camid)->first();
        if ($campaignContract != null) {
            $resp['error'] = "You already applied for this campaign";
            $resp['code'] = 403;
            goto end;
        } else {
            $campaignContract = new CampaignContract;
            $campaignContract->uuid = uniqid();
            $campaignContract->campaign_id = $camid;
            $campaignContract->influencer_id = $userid;
            $campaignContract->status = 2;
            $campaignContract->save();
        }


        $resp['data'] = $campaignContract->toArray();
        end:
        $response->getBody()->write(json_encode($resp));

        return $response;
    }

    function offerCampaign($request, $response, $args) {
        $userid = $request->getAttribute('userid');
        $camid = ($request->getAttribute("camid"));
        $influid = ($request->getAttribute("influid"));

        $resp = array();
        $resp['error'] = "";
        $resp['code'] = 200;
        $resp['data'] = "";

        if (empty($camid)) {
            $resp['error'] = "Some fields are missing or wrong";
            $resp['code'] = 400;
            goto end;
        }

        $user = User::where("uuid", "=", $userid)->first();

        if ($user->user_type == "influencer") {
            $resp['error'] = "Only brand can do that";
            $resp['code'] = 400;
            goto end;
        }

        $campaignContract = CampaignContract::where("influencer_id", "=", $influid)->where("campaign_id", "=", $camid)->first();
        if ($campaignContract != null) {
            $resp['error'] = "This influencer already applied for this campaign";
            $resp['code'] = 403;
            goto end;
        } else {
            $campaignContract = new CampaignContract;
            $campaignContract->uuid = uniqid();
            $campaignContract->campaign_id = $camid;
            $campaignContract->influencer_id = $influid;
            $campaignContract->status = 3;
            $campaignContract->save();
        }


        $resp['data'] = $campaignContract->toArray();
        end:
        $response->getBody()->write(json_encode($resp));

        return $response;
    }

    function getContractsForCampaign($request, $response, $args) {
        $userid = $request->getAttribute('userid');
        $camid = ($request->getAttribute("camid"));

        $resp = array();
        $resp['error'] = "";
        $resp['code'] = 200;
        $resp['data'] = "";

        if (empty($camid)) {
            $resp['error'] = "Some fields are missing or wrong";
            $resp['code'] = 400;
            goto end;
        }

        $user = User::where("uuid", "=", $userid)->first();


        $contracts = CampaignContract::where("campaign_id", "=", $camid)->get();
        if ($contracts != null) {
            foreach ($contracts as &$item) {
                $item['influencer'] = Influencer::where("user_id", "=", $item->influencer_id)->first();
            }
        }

        $resp['data'] = $contracts->toArray();
        end:
        $response->getBody()->write(json_encode($resp));

        return $response;
    }

    function cancelCampaign($request, $response, $args) {
        $userid = $request->getAttribute('userid');
        $camid = ($request->getAttribute("camid"));

        $resp = array();
        $resp['error'] = "";
        $resp['code'] = 200;
        $resp['data'] = "";

        if (empty($camid)) {
            $resp['error'] = "Some fields are missing or wrong";
            $resp['code'] = 400;
            goto end;
        }

        $user = User::where("uuid", "=", $userid)->first();
        $campaign = Campaign::where("uuid", "=", $camid)->first();
        if ($campaign->brand_id != $user->uuid) {
            $resp['error'] = "Permission denied to close this campaign";
            $resp['code'] = 400;
            goto end;
        }

        $campaign->status = 3;
        $campaign->save();

        $resp['message'] = 'Campaign was successfully closed';
        end:
        $response->getBody()->write(json_encode($resp));

        return $response;
    }

    function searchInfluencerList($request, $response, $args) {
        $userid = $request->getAttribute('userid');
        $user = User::where("uuid", "=", $userid)->first();

        $page_size = isset($_GET['page_size']) ? $_GET['page_size'] : 20;

        $data = $request->getParsedBody();
        $resp = array();
        $resp['error'] = "";
        $resp['code'] = 200;
        $resp['data'] = "";

        $query = Influencer::where(DB::raw('1'),'=', 1);

        if (!empty($data['tags'])) {
            $tag = UserInterest::whereIn("tag", $data['tags'])->get();
            $uuid = array();
            foreach ($tag as $t) {
                $uuid[] = $t->user_id;
            }
            $query = $query->whereIn("user_id", $uuid);
        }

        if (!empty($data['gender'])) {
            $query = $query->where("gender", "=", $data['gender']);
        }

        if (!empty($data['reach_max'])) {
            $query = $query->where("reach_num", "<", $data['reach_max']);
        }

        if (!empty($data['rate_max'])) {
            $query = $query->where("rate", "<", $data['rate_max']);
        }

        $influencers = $query->orderBy("created_at", "DESC")->paginate($page_size);

        $results = [];
        foreach($influencers as $influencer) {
            $results[] = $influencer->toProfileArray();
        }
        $resp['data']['results'] = $results;
        $resp['data']['next'] = $influencers->nextPageUrl();
        $resp['data']['prev'] = $influencers->previousPageUrl();
        $resp['data']['count'] = $influencers->total();
        $response->getBody()->write(json_encode($resp));

        return $response;
    }

    function getFaqList($request, $response, $args) {
        $userid = $request->getAttribute('userid');
        $user = User::where("uuid", "=", $userid)->first();

        $page_size = isset($_GET['page_size']) ? $_GET['page_size'] : 20;

        $data = $request->getParsedBody();

        $resp = array();
        $resp['error'] = "";
        $resp['code'] = 200;
        $resp['data'] = "";


        $campaigns = Faq::orderBy("created_at", "DESC")->paginate($page_size);

        $resp['data']['results'] = $campaigns->getCollection()->toArray();
        $resp['data']['next'] = $campaigns->nextPageUrl();
        $resp['data']['prev'] = $campaigns->previousPageUrl();
        $resp['data']['count'] = $campaigns->total();
        $response->getBody()->write(json_encode($resp));

        return $response;
    }
}