<?php

Route::group(['domain' => config('project.api_domain'), 'namespace' => 'Api', 'as' => 'api.', 'middleware' => ['cors']], function () {
    /* api.v1 */
    Route::group(['prefix' => 'v1', 'namespace' => 'v1'], function () {
        /* Home */
        Route::get('/', [
            'as' => 'v1.index',
            'uses' => 'WelcomeController@index',
        ]);

        /* Forum */
        Route::resource('articles', 'ArticlesController');
        Route::get('tags/{slug}/articles', [
            'as' => 'v1.tags.articles.index',
            'uses' => 'ArticlesController@index',
        ]);
        Route::get('tags', [
            'as' => 'v1.tags.index',
            'uses' => 'ArticlesController@tags',
        ]);
        Route::resource('attachments', 'AttachmentsController', ['only' => ['store', 'destroy']]);
        Route::resource('articles.attachments', 'AttachmentsController', ['only' => ['index']]);
        Route::resource('comments', 'CommentsController', ['only' => ['show', 'update', 'destroy']]);
        Route::resource('articles.comments', 'CommentsController', ['only' => ['index', 'store']]);
        Route::post('comments/{comments}/votes', [
            'as' => 'v1.comments.vote',
            'uses' => 'CommentsController@vote',
        ]);
    });

    /* User Registration */
    Route::post('auth/register', [
        'as' => 'users.store',
        'uses' => 'UsersController@store',
    ]);

    /**
     * Session
     *
     * 사용자가 확인되면 서버는 클라이언트에게 토큰을 반환한다.
     * 클라이언트는 토큰을 기억해야 한다.
     * 클라이언트는 리소스를 요청할 때 Authorization 헤더에 토큰을 달아서 보낸다.
     *
     * API 서비스는 세션을 유지하지 않기 때문에, 로그아웃이 필요없다.
     * 모든 API 요청은 Authorization 헤더값을 제시해야 하고,
     * 서버는 그 헤더값으로 사용자를 식별하여 인증/권한부여한다.
     */
    Route::post('auth/login', [
        'as' => 'sessions.store',
        'uses' => 'SessionsController@store',
    ]);

    Route::post('auth/refresh', [
        'middleware' => 'jwt.refresh',
        'as' => 'sessions.refresh',
        function () {
        },
    ]);

    /**
     * Social Login
     *
     * 소셜로그인은 클라이언트 측에서한다.
     * 클라이언트에서 소셜사용자가 확인되면 서버에 소셜사용자 정보를 던진다.
     * 서버는 받은 사용자 객체로 로그인인다. 없으면 만든다.
     * 로그인하면 서버는 클라이언트에게 토큰을 발급한다.
     */
    Route::post('social/{provider}', [
        'as' => 'social.login',
        'uses' => 'SocialController@store',
    ]);

    /**
     * Password Reminder
     *
     * 클라이언트가 비밀번호 바꾸기 요청을 하면 서버는 비밀번호 바꾸는 방법을 담은 메일을 보낸다.
     * 사용자가 메일에서 링크를 클릭하면 웹브라우저가 작동하고, 그 이후 모든 과정은 웹에서 이루어 진다.
     * 바꾼 비밀번호는 서버에 저장되어 있고, 다음번 클라이언트에서 바꾼 비밀번호로
     * 로그인을 시도하면 유효한 토큰을 발급 받을 수 있다.
     */
    Route::post('auth/remind', [
        'as' => 'remind.store',
        'uses' => 'PasswordsController@postRemind',
    ]);
});