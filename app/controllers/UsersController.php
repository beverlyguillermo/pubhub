<?php

namespace app\controllers;
use \app\base\View;
use \app\workers\Router;
use \app\workers\Auth;
use \app\workers\Hash;
use \app\workers\Session;
use \app\workers\Messages;

class UsersController extends \app\base\Controller
{
	protected $objectName = "Users";

	public function index()
	{
		$this->model->data["users"] = $this->model->findAll();
		$this->model->data["page_title"] = "Manage Users";
		$this->render("users/index");
	}

	public function login()
	{
		if (Auth::check()) {
		 	$this->router->redirect("/manager");
		}

		$this->model->data["page_title"] = "Login";
		$this->render("users/login");
	}

	public function register()
	{
		if ($this->router->request()->params("username")) {
			// Somehow check to make sure username isn't taken
			extract($this->router->request()->params());
			$this->model->create(array(
				"username" => $username,
				"password" => Hash::create($password)
			));
			$this->router->redirect("/manager/login");
		}
		$this->model->data["page_title"] = "Register";
		$this->render("users/register");
	}

	public function create()
	{
		$req = $this->router->request();
		$params = $this->router->request()->params();
		
		if ($req->isPost()) {
			//var_dump($params);
			if (isset($params["email"]) && $this->model->validate($params["email"], array("email", "emailDoesNotExist"))) {
				if ($this->model->create(array("email" => $params["email"]))) {
					$signUpLink = $this->router->request()->getUrl() . "/newuser/setup?id=" . urlencode($params["email"]);
					$message = \Swift_Message::newInstance()
						->setSubject("A new Hub Manager account is waiting for you.")
						->setFrom(array("dalexander@jhu.edu" => "Dave Alexander"))
						->setTo(array($params["email"]))
						->setCC(array("jrhodes@jhu.edu"))
						// ->setTo(array("jrhodes@jhu.edu"))
						->setBody("<div style='font-family:Myriad Pro, Lucida Grande, Helvetica, sans-serif; font-size: 150%; width:700px;'><h1>Welcome!</h1><p>We just created a Hub Manager account for your email address. To create your username and password, go to this link: <a href='{$signUpLink}'>{$signUpLink}</a></p><p>Thanks,<br>Dave (Hub Editor) and the Hub Team</p></div>", "text/html");
					$transport = \Swift_MailTransport::newInstance();
					$mailer = \Swift_Mailer::newInstance($transport);
					$result = $mailer->send($message);
					Messages::push("success", "High five! We created a user for " . $params["email"] . ".");
				} else {
					Messages::push("error", "Hm, the email looks good and it doesn't already exist, but we still couldn't save it to the database. Frustrating, isn't it?");
					Messages::push("notice", "Database message: " . $this->model->lastPDOErrors()->info[2]);
				}
			}
		
		}
		$this->render("users/create");
	}

	public function edit()
	{
		$this->render("users/edit", array("layout" => "layouts/default"));
	}

	public function delete()
	{
		$currentUser = Auth::loggedInUser();
		if (!$currentUser || $currentUser->role != "admin") {
			Messages::push("error", "Sorry, you're not allowed to do that.");
		}
		elseif ($this->model->id === $currentUser->id) {
			Messages::push("error", "You really don't want to delete your own account. Well, even if you do, you can't. Sorry!");
		}
		elseif ($this->model->delete() > 0) {
			Messages::push("success", "User was successfully deleted. Do you want to send them a nasty letter, too? Maybe spread some awful rumors about them?");
		}
		else { 
			Messages::push("error", "Sorry, the user you were trying to destroy didn't exist. Maybe somebody beat you to it?");
		}

		$this->router->redirect("/manager/users");
	}

	public function setup()
	{
		$req = $this->router->request();
		$params = $this->router->request()->params();
		$user = $this->model->findByField(array("email" => str_replace(" ", "+", urldecode($params["id"]))));
		$user = (object) array_shift($user);

		$this->log->addInfo("The user object grabbed from {$params['id']}", (array) $user);

		if (!empty($user->username) && !empty($user->hashed_password)) {
			$this->router->redirect("/manager/login");
		}

		if (empty($user) || !$user) {
			Messages::push("error", "Sorry, we can't find a user with this address: {$params['id']}");
		} elseif ($req->isPost()) {
			if ($params["password"] !== $params["verify_password"]) {
				Messages::push("error", "Careful now! Your passwords didn't match. Try again.");
			} elseif (empty($params["password"]) || empty($params["username"])) {
				Messages::push("error", "Oh, both the username and password fields are required. Try again please.");
			} elseif ($this->model->validate($params["username"], array("usernameDoesNotExist"))) {
				// Update the user
				if ($this->model->update($user->id, array("username" => $params["username"], "hashed_password" => Auth::createPassword($params["password"])))) {
					Messages::push("success", "Great, your account is all set up. You can log in now or come back later.");
					$this->router->redirect("/manager/login");
				}
			}		
		} else {
			Messages::push("info", "Hey, are you " . $params["id"] . "?");
		}
		
		$this->render("users/edit", array("layout" => "layouts/hub.twig"));
	}

	public function logout() {
		Auth::logout();
		$this->router->redirect("/manager/login");
	}

}