<?php
require_once 'restd.php';

define(BASE_PATH, '/example.php');

// example request:
// 		curl -XGET localhost/example.php/status
class GetStatus extends JSONHandler {
	protected function handleInternal($http_method, $path, $args, $data) {
		$this->setResponse(array("status" => "OK"));
	}
}

// example request:
// 		curl -XGET localhost/example.php/artefact/123
class GetArtefact extends JSONHandler {
	protected function handleInternal($http_method, $path, $args, $data) {
		$this->setResponse(array("artefact" => array(
			"id" => $args['id'],
			"description" => "mocked artefact"
		)));
	}
}

// example request:
// 		curl -XPOST localhost/example.php/artefact -d '{"artefact": {"description": "New artefact"}}'
class NewArtefact extends JSONHandler {
	protected function handleInternal($http_method, $path, $args, $data) {
		$this->setResponse(array("created_artefact" => array(
			"id" => 123,
			"description" => $data['artefact']['description']
		)));
	}
}

// example request:
// 		curl -XDELETE localhost/example.php/artefact/123
class DeleteArtefact extends JSONHandler {
	protected function handleInternal($http_method, $path, $args, $data) {
		$this->setResponse(array("deleted_artefact" => array(
			"id" => $args['id'],
			"description" => "mocked artefact"
		)));
	}
}

// example request:
// 		curl -XPUT localhost/example.php/artefact/123 -d '{"artefact": {"description": "Modified description"}}'
class PutArtefact extends JSONHandler {
	protected function handleInternal($http_method, $path, $args, $data) {
		$this->setResponse(array("created_artefact" => array(
			"id" => $args['id'],
			"description" => $data['artefact']['description']
		)));
	}
}

$router = new Router(BASE_PATH);

$router->addHandler('GET', '/status', new GetStatus);

$router->addHandler('GET', '/artefact/{id}', new GetArtefact);
$router->addHandler('POST', '/artefact', new NewArtefact);
$router->addHandler('DELETE', '/artefact/{id}', new DeleteArtefact);
$router->addHandler('PUT', '/artefact/{id}', new PutArtefact);

$router->process();
