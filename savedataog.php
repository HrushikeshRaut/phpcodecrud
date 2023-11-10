<?php

require 'vendor/autoload.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost:8080");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

$mongoClient = new MongoDB\Client("mongodb://localhost:27017");
$admissionDB = $mongoClient->selectDatabase('admi');
$admissionCollection = $admissionDB->selectCollection("Entries");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    $userData = $admissionCollection->find()->toArray();
    echo json_encode($userData);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = json_decode(file_get_contents('php://input'), true);

    $errors = validateFormData($formData);

    if (empty($errors)) {
        $result = $admissionCollection->insertOne($formData);

        if ($result->getInsertedCount() === 1) {
            $response = ['message' => 'Data saved successfully'];
        } else {
            $response = ['message' => 'Error saving data'];
        }
    } else {
        $response = ['errors' => $errors];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents('php://input'), $deleteParams);
    $userId = $deleteParams['userId'];

    $result = $admissionCollection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($userId)]);

    if ($result->getDeletedCount() === 1) {
        $response = ['message' => 'User deleted successfully'];
    } else {
        $response = ['message' => 'Error deleting user'];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $data['userId'];

    $result = $admissionCollection->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($userId)],
        ['$set' => $data] 
    );

    if ($result->getModifiedCount() === 1) {
        $response = ['message' => 'User updated successfully'];
    } else {
        $response = ['message' => 'Error updating user'];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
}

function validateFormData($data)
{
    $errors = [];

    if (empty($data['firstName'])) {
        $errors['firstName'] = 'First Name is required';
    }

    if (empty($data['lastName'])) {
        $errors['lastName'] = 'Last Name is required';
    }

    if (empty($data['dateOfBirth'])) {
        $errors['dateOfBirth'] = 'This is required';
    }

    if (empty($data['email'])) {
        $errors['email'] = 'This is required';
    }

    if (empty($data['residenceAddress'])) {
        $errors['residenceAddress'] = 'This is required';
    }

    return $errors;
}

?>