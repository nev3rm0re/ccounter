<?php
    session_start();

    require_once('setup.php');

    if (array_key_exists('meal', $_POST)) {
        $meal = R::load('meal', $_POST['meal']);
        if ($meal->id === 0) {
            $meal = R::dispense('meal');

            $meal_number = R::count('meal', 'date = ?', array(date('Y-m-d'))) + 1;

            $meal->name = date('d.m.Y'). ' - Meal #' . $meal_number;
            $meal->date = date('Y-m-d');
            $meal->preset = $_POST['selected_preset'];

            $meal_id = R::store($meal);
        } else {
            $meal_id = $meal->id;
            $meal->preset = $_POST['selected_preset'];
            R::store($meal);
        }
    }

    if (!array_key_exists('product_id', $_POST)) {
        $_POST['product_id'] = array();
    }

    // load meal
    // if no meal is present - create new one

    // default meal name - date, meal number

    $posted_products = array();
    foreach ($_POST['product_id'] as $n => $product_id) {
        if (array_key_exists('record_id', $_POST) && array_key_exists($n, $_POST['record_id'])) {
            $record_id = $_POST['record_id'][$n];
        } else {
            $record_id = null;
        }

        $posted_products[] = array(
            'meal_id' => $meal_id,
            'record_id' => $record_id,
            'product_id' => $product_id,
            'amount' => $_POST['amount'][$n],
            'preset' => $_POST['presets'][$n]
        );
    }

    $meal_totals = array(
        'calories' => 0,
        'protein' => 0,
        'carbs' => 0,
        'fat' => 0
    );

    foreach ($posted_products as $key => $p) {
        if ($p['record_id'] !== null) {
            // existing record
            $saved = R::load('saved_product', $p['record_id']);
        } else {
            $saved = R::dispense('saved_product');
        }

        if ($p['amount'] == 0) {
            R::trash($saved);
        } else {
            $product = R::load('product', $p['product_id']);
            $multiplier = $p['amount'] / 100;
            $p['total_calories'] = $product->ccal * $multiplier;
            $p['total_protein'] = $product->protein * $multiplier;
            $p['total_carbs'] = $product->carbs * $multiplier;
            $p['total_fat'] = $product->fat * $multiplier;
            $p['name'] = $product['name'];

            $meal_totals['calories'] += $p['total_calories'];
            $meal_totals['protein'] += $p['total_protein'];
            $meal_totals['carbs'] += $p['total_carbs'];
            $meal_totals['fat'] += $p['total_fat'];

            $saved->import($p);
            R::store($saved);

            $meal->import($meal_totals);
            R::store($meal);
        }
    }

    $_SESSION['message'] = array('type' => 'success', 'message' => 'Meal saved');
    header('Location: index.php?meal='.$meal_id);


