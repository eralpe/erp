<?php
  require_once 'config/db.php';

  if (isset($_GET['id'])) {
      $recipe_id = intval($_GET['id']);
      $stmt = $pdo->prepare("SELECT r.*, p.name as product_name 
                            FROM recipes r 
                            JOIN products p ON r.product_id = p.id 
                            WHERE r.id = ?");
      $stmt->execute([$recipe_id]);
      $recipe = $stmt->fetch(PDO::FETCH_ASSOC);

      $stmt = $pdo->prepare("SELECT rd.raw_material_id, rd.quantity, rd.unit_type 
                            FROM recipe_details rd 
                            WHERE rd.recipe_id = ?");
      $stmt->execute([$recipe_id]);
      $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

      header('Content-Type: application/json');
      echo json_encode(['id' => $recipe['id'], 'name' => $recipe['name'], 'product_id' => $recipe['product_id'], 'details' => $details]);
  }
  ?>