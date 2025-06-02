<?php
require_once "confing.php";

header('Content-Type: application/json');

$resource = $_GET["resource"] ?? null;
$filterType = $_GET["filterType"] ?? null;
processResourceType($resource, $filterType, $db);
function processGetType($db)
{
    $stmt = "SELECT id, name FROM product_types ORDER BY name";
    try {
        $query = $db->query($stmt);
        $result = $query->fetchAll(pdo::FETCH_ASSOC);
        $newresault = [];
        foreach ($result as $row) {
            $url = "http://localhost:63342/Uebung4/index.php?resource=products&filterType=" . $row['id'];
            $newresault[] = [
                "productTypes" => $row["name"],
                "URL" => $url,
            ];


        }
        echo json_encode($newresault, JSON_UNESCAPED_UNICODE);

    } catch (PDOException $e) {
        die("DB Connection failed: " . $e->getMessage());
    }
}

function processProduct($db, $filterType)
{
    if (isset($filterType)) {
        $sql = "SELECT t.name AS productTypeName, p.name AS productName
 FROM product_types t
JOIN products p ON t.id = p.id_product_types
WHERE t.id = {$filterType}";
        $query = $db->query($sql);
        $result = $query->fetchAll(pdo::FETCH_ASSOC);
        // $resultDatabase = json_encode($result, JSON_UNESCAPED_UNICODE);
        // echo $resultDatabase;
        $groupData = [];
        foreach ($result as $row) {
            $typeName = $row["productTypeName"];
            $productName = $row["productName"];
            if (!isset($groupData[$typeName])) {
                $groupData[$typeName] = [];
            }
            $groupData[$typeName][] = [
                "name" => $productName,

            ];
        }
        $output = [];
        foreach ($groupData as $typeName => $products) {
            $output[] = [
                'productType' => $typeName,
                'products' => $products

            ];

        }
        $url = "http://localhost/Uebung3/index.php?resource-types";
        $output[] = ['url' => $url];
        echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

}

function processCart($db)
{
    $article = $_GET["articleID"] ?? null;
    $method = $_SERVER["REQUEST_METHOD"];
    $orderID = $_GET["orderID"] ?? 5; // weil Sie nicht gesagt haben dass zu wecher OrderId gehört, ich habe vorgenommen, es es nicht gibt. nimmt es ID 5

    if ($method == "POST" && isset($article)) {
        createOrUpdateOrder($db, $orderID, $article);


    }
}

function processResourceType($resource, $filterType, $db)
{

    if ($resource == "types") {
        processGetType($db);
    } else if ($resource == "products") {
        processProduct($db, $filterType);


    } else if ($resource == "cart") {
        processCart($db);
    }

}

function createOrUpdateOrder($db, $orderID, $article)
{
    $sql = $db->prepare("SELECT amount from order_positions where orders_id = ? AND product_id = ?");
    $sql->execute([$orderID, $article]);
    $row = $sql->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $newAmount = $row["amount"] + 1;
        $update = $db->prepare("UPDATE order_positions SET amount = ? WHERE orders_id = ? AND product_id = ?");
        $update->execute([$newAmount, $orderID, $article]);
        echo json_encode([
            "status" => "ok"
        ]);
    } else {
        $insert = "INSERT INTO order_positions (orders_id, product_id, amount) VALUES (?, ?, ?)";
        $stmt = $db->prepare($insert);
        $stmt->execute([$orderID, $article, 1]);
        echo json_encode([
            "status" => "ok"
        ]);

    }
}

