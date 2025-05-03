<?php
require 'db.php';

$sql = "SELECT 
            id, title, author, description, image, video_link, resource_link, upload_resource, category, rating, price
        FROM courses
        ORDER BY upload_resource ASC";

$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $category = $row['category'];

    if (!isset($data[$category])) {
        $data[$category] = [
            'name' => $category,
            'courses' => []
        ];
    }

    $data[$category]['courses'][] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'author' => $row['author'],
        'description' => $row['description'],
        'image' => $row['image'],
        'video_link' => $row['video_link'],
        'resource_link' => $row['resource_link'],
        'upload_resource' => $row['upload_resource'],
        'rating' => (float)$row['rating'],
        'price' => (float)$row['price']
    ];
}

$data = array_values(array_map(function ($cat) {
    $cat['courses'] = array_values($cat['courses']);
    return $cat;
}, $data));

header('Content-Type: application/json');
echo json_encode(['categories' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
