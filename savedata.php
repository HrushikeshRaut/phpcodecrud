<?php
  
require 'vendor/autoload.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost:8080");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE , OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$mongoClient = new MongoDB\Client("mongodb://localhost:27017");
$admissionDB = $mongoClient->selectDatabase('admi');
$admissionCollection = $admissionDB->selectCollection("Entries");

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            handleGet($admissionCollection);
            break;
        case 'POST':
            handlePost($admissionCollection);
            break;
        case 'DELETE':
            handleDelete($admissionCollection);
            break;
        case 'PUT':
            handlePut($admissionCollection);
            break;
        default:
            echo json_encode(['message' => 'Method not allowed']);
            http_response_code(405);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
    http_response_code(500);
}

function handleGet($collection)
{
    $userData = $collection->find()->toArray();
    echo json_encode($userData);
}

function handlePost($collection)
{
    $formData = json_decode(file_get_contents('php://input'), true);
    $errors = validateFormData($formData);

    if (empty($errors)) {
        $result = $collection->insertOne($formData);
        $response = $result->getInsertedCount() === 1 
            ? ['message' => 'Data saved successfully'] 
            : ['message' => 'Error saving data'];
        echo json_encode($response);
    } else {
        echo json_encode(['errors' => $errors]);
        http_response_code(400);
    }
}

function handleDelete($collection)
{
     $userId = $_GET['userId'] ?? '';

    if (!isValidObjectId($userId)) {
        echo json_encode(['error' => 'Invalid user ID' , $userId ]);
        http_response_code(400);
        return;
    }

    $result = $collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($userId)]);
    echo json_encode(['result is' , $result]);
    $response = $result->getDeletedCount() === 1
        ? ['message' => 'User deleted successfully']
        : ['message' => 'Error deleting user'];

   header('Content-Type: application/json');
    echo json_encode($response);
}

function handlePut($collection)
{
    $userId = $_GET['updatedUser._id'] ?? '';

    if (!isValidObjectId($userId)) {
        echo json_encode(['error' => 'Invalid user ID']);
        http_response_code(400);
        return;
    }

    unset($data[$UserId]); 
    $errors = validateFormData($data);

    if (empty($errors)) {
        $result = $collection->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($userId)],
            ['$set' => $data]
        );

        $response = $result->getModifiedCount() === 1
            ? ['message' => 'User updated successfully']
            : ['message' => 'Error updating user'];
        echo json_encode($response);
    } else {
        echo json_encode(['errors' => $errors]);
        http_response_code(400);
    }
      header('Content-Type: application/json');
}

function validateFormData($data)
{
    $errors = [];

    if (empty($data['firstName']) || !is_string($data['firstName'])) {
        $errors['firstName'] = 'First Name is required and must be a string';
    }

    if (empty($data['lastName']) || !is_string($data['lastName'])) {
        $errors['lastName'] = 'Last Name is required and must be a string';
    }

    if (empty($data['dateOfBirth']) || !validateDate($data['dateOfBirth'])) {
        $errors['dateOfBirth'] = 'Valid Date of Birth is required';
    }

    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Valid Email is required';
    }

    if (empty($data['residenceAddress']) || !is_string($data['residenceAddress'])) {
        $errors['residenceAddress'] = 'Residence Address is required and must be a string';
    }

    return $errors;
}

function isValidObjectId($id)
{
    return preg_match('/^[a-f\d]{24}$/i', $id);
}

function validateDate($date, $format = 'Y-m-d')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

?>