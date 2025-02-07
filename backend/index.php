<?php

include('core/Router.php');
$router = new Router();



/*
 *   AUTHENTICATION
 */
include('classes/Auth.php');
$auth = new Auth();

// Login
$router->post('/auth/login', $auth, 'login');

$router->post('/auth/check-token', $auth, 'checkToken');



/*
 *   Q&A
 */
include('classes/QA.php');
$qa = new QA();

// Get all questions (also handles search/filters)
$router->get('/questions', $qa, 'allQuestions');

// Get question with answers
$router->get('/question', $qa, 'question');

// Create Question
$router->post('/questions', $qa, 'createQuestion');

// Create Answer
$router->post('/answers', $qa, 'createAnswer');

// Delete Question
$router->delete('/questions', $qa, 'deleteQuestion');

// Delete Answer
$router->delete('/answers', $qa, 'deleteAnswer');


/*
 *   RUN ROUTER
 */
$router->run();