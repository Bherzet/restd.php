# restd.php
A minimalist library to ease up development of REST APIs in PHP.

## Example

	<?php
	require_once 'restd.php';

	define(BASE_PATH, '/index.php');

	class GetCurrentUser extends JSONHandler {
		protected function handleInternal($http_method, $path, $args, $data) {
			// ...
		}
	}

	// ... other handlers

	$router = new Router(BASE_PATH);
	$router->setDefaultHandler(new DefaultHandler);

	$router->addHandler('GET',    '/user',               new GetCurrentUser);
	$router->addHandler('POST',   '/user',               new CreateUser);
	$router->addHandler('GET',    '/user/{userID}',      new GetUser);
	$router->addHandler('PUT',    '/user/{userID}',      new EditUser);
	$router->addHandler('DELETE', '/user/{userID}',      new DeleteUser);

	$router->addHandler('GET',    array('/', '/status'), new GetStatusHandler);

	$router->process();

## Tutorial

### 1. Configuring router
**Router** is the key component. It's responsible for calling handlers based on the current URL.

Router needs to know path to the current file. To figure out what that path is, simply use something like

	<?php echo $_SERVER['REQUEST_URI'];

Let's say you put it to a file named `index.php`. Then simply call:

	curl -XGET mydomain.com/index.php

Whatever it will return is the base path.

### 2. Writing handlers
**Handler** is a component that serves specific request(s). Implementing a handler is as simple as writing a class with
single method that accepts these arguments:


* `$http_method` simply tells you what HTTP method have been used to make the request. This comes handy when you use one
handler for multiple requests.

* `$path` is a request path as string and - once again - is only useful when you use one handler for multiple requests.

* `$args` is an associative array which contains all positional arguments. Positional arguments are parts of the path closed between
curly brackets.

* `$data` is string containing raw request data sent with `POST`, `PUT` or `DELETE`.

#### 2.1 JSONHandler
`JSONHandler` implements interface `Handler` and allows you to easily send JSON responses. Here's an example incorporating JSONHandler
to return some error message:

	class DefaultHandler extends JSONHandler {
		protected function handleInternal($http_method, $path, $args, $data) {
			$this->setResult(400, array("error" => "No handler matches path ".$path." for HTTP method ".$http_method."."));
		}
	}

Arguments `$http_method`, `$path` and `$args` are the same as for `Handler`. Argument `$data` contains decoded JSON body as an associative
array.

Instead of `setResult`, you can also call `setResponseCode` and `setResponse`. Calling `setRawResponse` allows you to skip JSON encoding and send
raw string. This might be useful when you already have your JSON response as a string.

`JSONHandler` automatically catches all exceptions (instances of `Exception`) and sets code to 500 in such case.

### 3. Aliases
You can have the same instance of handler assigned to multiple paths. This means you created an alias. Accessing any of these paths
will trigger the same action.

You have two options. The simplest option is to pass array to `addHandler()`:

	$router->addHandler('GET', array('/status', '/v2.1/status'), new Version21\GetStatusHandler);

Or you can simply pass **the same instance** to addHandler multiple times:

	$getStatusHandler = new GetStatusHandler();
	$router->addHandler('GET', '/status', $getStatusHandler);
	$router->addHandler('GET', '/v2.1/status', $getStatusHandler);

### 4. Multiple invocations
One request can invoke more than one handler. This will happen when multiple path masks matches **and** the instances are different. This
way you can create, for example, logger:

	$router->addHandler('GET', '/status', new GetStatusHandler);
	$router->addHandler('GET', '/{method}', new LoggingHandler);

Both LoggingHandler and GetStatusHandler will be executed while handling GET request for `/status`.

### 5. Default handler
Default handler is triggered when any other handler got executed. Usually, you will want to return some error code to let user know what happened.

If needed, you can set default handler using method `setDefaultHandler` on router object. Value might be `null`, in which case no default handler would
be used.

If not set, `NotFoundErrorHandler` is used.

