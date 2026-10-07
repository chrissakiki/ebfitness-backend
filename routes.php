<?php

// $router->get('/users', 'UserController@index');
// $router->get('/users/{id}', 'UserController@index');

//Homepage

$router->get('/homepage', 'HomepageController@index');

//Banners
$router->get('/banners', 'BannerController@index');
$router->get('/banners/{id}', 'BannerController@show');
$router->post('/banners/{id}', 'BannerController@update', ['admin']);

//Sections
$router->get('/sections', 'SectionController@index');
$router->get('/sections/{id}', 'SectionController@show');
$router->get('/sections/type/{type}', 'SectionController@showByType');
$router->post('/sections', 'SectionController@create', ['admin']); //not for long
$router->post('/sections/{id}', 'SectionController@update', ['admin']);

//Services
$router->get('/services', 'ServiceController@index');
$router->get('/services/{id}', 'ServiceController@show');
$router->post('/services/{id}', 'ServiceController@update', ['admin']);
$router->delete('/services/{id}', 'ServiceController@destroy', ['admin']);

// Achievements
$router->get('/achievements', 'AchievementController@index');
$router->get('/achievements/{id}', 'AchievementController@show');
$router->post('/achievements', 'AchievementController@store',);
$router->post('/achievements/{id}', 'AchievementController@update',);
$router->delete('/achievements/{id}', 'AchievementController@destroy', ['admin']);


// Testimonials
$router->get('/testimonials', 'TestimonialController@index');
$router->get('/testimonials/{id}', 'TestimonialController@show');
$router->post('/testimonials', 'TestimonialController@store', ['admin']);
$router->put('/testimonials/{id}', 'TestimonialController@update', ['admin']);
$router->delete('/testimonials/{id}', 'TestimonialController@destroy', ['admin']);

// Milestones
$router->get('/milestones', 'MilestoneController@index');
$router->get('/milestones/{id}', 'MilestoneController@show');
$router->post('/milestones', 'MilestoneController@store', ['admin']);
$router->put('/milestones/{id}', 'MilestoneController@update', ['admin']);
$router->delete('/milestones/{id}', 'MilestoneController@destroy', ['admin']);

// Users
$router->post('/login', 'UserController@login');
$router->post('/register', 'UserController@register');
$router->post('/logout', 'UserController@logout');
$router->get('/admin/checkauth', 'UserController@checkauth');

// Mentorship Program
$router->get('/mentorships', 'MentorshipController@index', ['admin']);
$router->get('/mentorships/{id}', 'MentorshipController@show', ['admin']);
$router->post('/mentorships', 'MentorshipController@store');
$router->delete('/mentorships/{id}', 'MentorshipController@destroy', ['admin']);
