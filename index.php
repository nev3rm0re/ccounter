<?php
    require_once('setup.php');

    $presets = array(
        'rest' => array(
            'calories' => 2100,
            'protein' => 220,
            'carbs' => 150,
            'fat' => 66
        ),
        'workout' => array(
            'calories' => 3000,
            'protein' => 220,
            'carbs' => 410,
            'fat' => 52
        )
    );

    foreach (R::find('product') as $product) {
        $products[$product->id] = $product->export();
        $products[$product->id]['calories'] = $product->ccal;
    }

    function create_macro_data($product) {
        $data = array($product['ccal'], $product['protein'], $product['carbs'], $product['fat']);
        return implode(',', $data);
    }

    function amount_format($amount) {
        return number_format($amount, 2, '.', ' ');
    }

    function percentage_format($percentage) {
        return number_format(round($percentage * 100, 2), 2) . '%';
    }

    if (array_key_exists('meal', $_GET)) {
        $meal_id = $_GET['meal'];
    } else {
        $meal_id = 0;
    }

    $saved_products = R::find('saved_product', 'meal_id = ?', array($meal_id));

    $grand_totals = array(
        'calories' => 0,
        'protein' => 0,
        'carbs' => 0,
        'fat' => 0
    );

    foreach ($saved_products as $key => $val) {
        $product = $products[$val['product_id']];

        $totals = $macros = array();
        foreach (array('calories', 'protein', 'carbs', 'fat') as $macro) {
            $macros[$macro] = (float) $product[$macro];
            $totals[$macro] = (float) $val['total_'.$macro];
            $grand_totals[$macro] += $totals[$macro];
        }
        $saved_products[$key]['totals'] = $totals;
        $saved_products[$key]['macros'] = $macros;
    }

    $meals = R::find('meal', '1 ORDER BY date DESC LIMIT 3');

    if ($meal_id > 0) {
        $current_meal = R::load('meal', $meal_id);
    } else {
        $current_meal = null;
    }

    if ($current_meal && !empty($current_meal->preset)) {
        $current_preset = $presets[$current_meal->preset];
    } else {
        $current_preset = $presets['rest'];
    }
    session_start();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Meal planner</title>
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
    <script src="../bootstrap/js/bootstrap.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/underscore.js/1.4.2/underscore-min.js"></script>
    <script src="js/chosen/chosen.jquery.min.js"></script>
    <link rel="stylesheet" href="js/chosen/chosen.css">
    <script src="js/product.js"></script>
    <style>
    .oh,.ot,.tt{float:left;padding:0 2% 2% 0;width:48%}.ot{width:31%}.tt{width:65%}.cl{clear:both}
    td.macros, th.macros {
        text-align: right;
    }
    </style>
</head>
<body>
    <div class="container">
        <?php if (array_key_exists('message', $_SESSION)): ?>
            <div class="alert alert-<?= $_SESSION['message']['type']?>">
                <?= $_SESSION['message']['message']; ?>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif;?>
        <form action="action.php" method="post">
        <div class="row">
            <div class="span12">
                <table class="table table-condensed table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Meal</th>
                            <th>Calories</th>
                            <th>Protein</th>
                            <th>Carbs</th>
                            <th>Fat</th>
                            <th>&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($meals as $meal): ?>
                            <tr>
                                <td><?= $meal->name ?></td>
                                <td class="macros"><?= round($meal->calories, 2); ?></td>
                                <td class="macros"><?= round($meal->protein, 2) ?></td>
                                <td class="macros"><?= round($meal->carbs, 2) ?></td>
                                <td class="macros"><?= round($meal->fat, 2) ?></td>
                                <td class="span1">
                                    <a class="btn btn-mini" href="?meal=<?= $meal->id?>">Load</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                </table>
                <div class="row">
                    <div class="span6">
                        <label>Meal:</label>
                        <select name="meal">
                            <?php foreach ($meals as $meal): ?>
                            <option value="<?= $meal->id?>" <?php if ($meal_id === $meal->id): ?>selected<?php endif;?>><?= $meal->name ?></option>
                            <?php endforeach; ?>
                            <option value="0" <?php if ($meal_id === 0): ?>selected<?php endif;?>>New meal</option>
                        </select>
                        <a href="<?= $_SERVER['PHP_SELF']?>" class="btn">New</a>
                    </div>
                    <div class="span6" style="text-align:right">
                        <label for="day_preset">Preset</label>
                        <select id="day_preset" name="selected_preset">
                            <option value="rest" <?php if ($current_meal->preset== 'rest'):?>selected<?php endif;?>>Rest</option>
                            <option value="workout" <?php if ($current_meal->preset == 'workout'):?>selected<?php endif;?>>Workout</option>
                        </select>
                    </div>
                </div>

            </div>
        </div>
        <table class="table table-condensed table-bordered meal">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Weight</th>
                    <th>Units</th>
                    <th style="width: 75px">Calories</th>
                    <th style="width: 75px">Protein</th>
                    <th style="width: 75px">Carbs</th>
                    <th style="width: 75px">Fat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($saved_products as $id => $product): ?>
                <tr class="saved" data-totals='<?= json_encode($product['totals']) ?>'
                    data-macros='<?= json_encode($product['macros']) ?>'>
                    <input type="hidden" name="record_id[]" value="<?= $id ?>">
                    <input type="hidden" name="product_id[]" value="<?= $product['product_id']?>">
                    <td><?= $product['name']?></td>
                    <td>
                        <input type="text" name="amount[]"
                            class="input-mini amount"
                            value="<?= $product['amount']?>"></td>
                    <td>
                        <select name="presets[]"
                            class="presets input-mini">
                            <option value="1">g</option>
                        </select>
                    </td>
                    <td class="macros calories">
                        <?= amount_format($product['total_calories'])?>
                    </td>
                    <td class="macros protein"><?= amount_format($product['total_protein'])?></td>
                    <td class="macros carbs"><?= amount_format($product['total_carbs'])?></td>
                    <td class="macros fat"><?= amount_format($product['total_fat']) ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3"></th>
                    <th class="macros calories"><?= amount_format($grand_totals['calories']);?></th>
                    <th class="macros protein"><?=  amount_format($grand_totals['protein'])?></th>
                    <th class="macros carbs"><?=  amount_format($grand_totals['carbs']);?></th>
                    <th class="macros fat"><?= amount_format($grand_totals['fat']); ?></th>
                </tr>
                <tr class="preset_percentage">
                    <th colspan="3"></th>
                    <th class="macros calories"><?= percentage_format($grand_totals['calories'] / $current_preset['calories'])?></th>
                    <th class="macros protein"><?= percentage_format($grand_totals['protein'] / $current_preset['protein'])?></th>
                    <th class="macros carbs"><?= percentage_format($grand_totals['carbs'] / $current_preset['carbs'])?></th>
                    <th class="macros fat"><?= percentage_format($grand_totals['fat'] / $current_preset['fat'])?></th>
                </tr>
                <tr>
                    <th colspan="7" style="text-align:right">
                        <button type="submit" class="btn btn-primary">Save</button>
                    </th>
                </tr>
            </tfoot>
        </table>
        </form>
        <div class="row">
            <div class="span12">
                <select name="product" class="chosen">
                    <option>Choose...</option>
                    <?php foreach ($products as $id => $product): ?>
                        <option data-id="<?= $id ?>" data-macros="<?= create_macro_data($product)?>"><?= $product['name']?></option>
                    <?php endforeach; ?>
                </select>
                <a href="index-old.php?action=list" class="btn">Product list</a>
            </div>
        </div>
        <h2>Presets</h2>
        <table class="table table-bordered table-condensed">
            <tr>
                <td class="span8">&nbsp;</td>
                <th style="width: 75px">Calories</th>
                <th style="width: 75px">Protein</th>
                <th style="width: 75px">Carbs</th>
                <th style="width: 75px">Fat</th>
            </tr>
            <tr>
                <th>Rest day</th>
                <td><?= $presets['rest']['calories'] ?> cal</td>
                <td><?= $presets['rest']['protein'] ?>g</td>
                <td><?= $presets['rest']['carbs'] ?>g</td>
                <td><?= $presets['rest']['fat'] ?>g</td>
            </tr>
            <tr>
                <th>Workout day</th>
                <td><?= $presets['workout']['calories']?> cal</td>
                <td><?= $presets['workout']['protein']?>g</td>
                <td><?= $presets['workout']['carbs']?>g</td>
                <td><?= $presets['workout']['fat']?>g</td>
            </tr>
        </table>
    </div>
    <script>
        var use_tables = true;
        var presets = {
            4: {
                "g": 1,
                "egg": 60
            }
        }
        var create_product_presets = function(id) {
            // get presets
            var preset = presets[id];
            $select = $('<select class="presets input-mini"></presets>');
            for (name in preset) {
                $option = $('<option value="' + preset[name] + '">' + name + '</option>');
                $select.append($option);
            }

            return $select;
        }

        var update_totals = function() {
            var totals = new Product;

            get_product_list_rows().each(function(i, e) {
                totals.add($(e).data('totals'));
            });

            var $totals = row_get_totals();

            $totals.find('.calories').text(totals.calories.toFixed(2));
            $totals.find('.protein').text(totals.protein.toFixed(2));
            $totals.find('.carbs').text(totals.carbs.toFixed(2));
            $totals.find('.fat').text(totals.fat.toFixed(2));

            var day_preset = $('#day_preset').val();
            var presets = <?= json_encode($presets) ?>;

            var $percentages = $('.preset_percentage');
            $percentages.find('.calories').text(((totals.calories / presets[day_preset].calories) * 100).toFixed(2) + '%');
            $percentages.find('.protein').text(((totals.protein / presets[day_preset].protein) * 100).toFixed(2) + '%');
            $percentages.find('.carbs').text(((totals.carbs / presets[day_preset].carbs) * 100).toFixed(2) + '%');
            $percentages.find('.fat').text(((totals.fat / presets[day_preset].fat) * 100).toFixed(2) + '%');
        }

        var update_row_macros = function() {
            var preset;

            var $this = $(this);
            var $row = row_get_row($this);

            var $macros = $row.find('.macros');

            var data = $row.data('macros');

            console.log('update_row_macros, data', data);

            preset = $row.find('.presets').val();
            var multiplier = preset !== null ? parseFloat(preset) : 1;
            var amount = parseFloat($row.find('.amount').val());

            var row_totals = data.multiply(amount * multiplier / 100);

            row_set_macros($row, row_totals);

            $row.data('totals', row_totals);

            $macros.find('span.calories').text(row_totals.calories.toFixed(2));
            $macros.find('span.protein').text(row_totals.protein.toFixed(2));
            $macros.find('span.carbs').text(row_totals.carbs.toFixed(2));
            $macros.find('span.fat').text(row_totals.fat.toFixed(2));

            update_totals();
        }

        var build_row = function() {
            return $('<div class="row">'
                + '<div class="span2"></div>'
                + '<div class="span2"></div>'
                + '<div class="span2"></div>'
                + '<div class="macros span6">'
                + '<span class="calories"></span>'
                + '<span class="protein"></span>'
                + '<span class="carbs"></span>'
                + '<span class="fat"></span>'
                + '</div>');
        }

        var row_get_totals = function() {
            return $('#totals');
        }

        var get_product_list_rows = function() {
            return get_product_list().find('.row');
        }

        var row_get_row = function($input) {
            return $input.parents('.row');
        }

        var row_set_name = function($row, name) {
            $row.find('div:eq(0)').text(name);
            return $row;
        }

        var get_product_list = function() {
            return $('#products');
        }

        var row_set_macros = function($row, macros) {
            $row.data('macros', macros);

            var $macros = $('<div class="macros span6"></div>');
            $macros.find('.calories').text(macros.calories);
            $macros.find('.protein').text(macros.protein);
            $macros.find('.carbs').text(macros.carbs);
            $macros.find('.fat').text(macros.fat);

            $row.append($macros);
        }

        var row_create_amount = function($row) {
            $input = $('<input type="text" value="100" class="amount input-mini">');
            $input.change(update_row_macros);

            $row.find('div:eq(1)').append($input);

            return $input;
        }

        var row_create_presets = function($row, product_id) {
            $presets = create_product_presets(product_id);
            $presets.change(update_row_macros);
            $row.find('div:eq(2)').append($presets);
        }

        if (use_tables) {
            build_row = function(product_id) {
                var $row = $('<tr>'
                    + '<td class="name"></td>'
                    + '<td></td>'
                    + '<td></td>'
                    + '<td class="calories"></td>'
                    + '<td class="protein"></td>'
                    + '<td class="carbs"></td>'
                    + '<td class="fat"></td>'
                    + '</tr>');
                $row.append($('<input type="hidden" name="product_id[]" value="' + product_id + '">'));
                return $row;
            }

            get_product_list_rows = function() {
                return get_product_list().find('tr');
            }

            row_get_totals = function() {
                return $('table tfoot tr:eq(0)');
            }

            row_get_row = function($input) {
                return $input.parents('tr');
            }
            row_set_name = function($row, name) {
                $row.find('.name').text(name);
                return $row;
            }
            get_product_list = function() {
                return $('table.meal tbody');
            }
            row_set_macros = function($row, macros) {
                if (!$row.data('macros')) {
                    $row.data('macros', macros);
                }
                $row.find('.calories').text(macros.calories.toFixed(2));
                $row.find('.protein').text(macros.protein.toFixed(2));
                $row.find('.carbs').text(macros.carbs.toFixed(2));
                $row.find('.fat').text(macros.fat.toFixed(2));

                return $row;
            }

            row_create_amount = function($row) {
                $input = $('<input type="text" value="100" name="amount[]" class="amount input-mini">');
                // $input.change(update_row_macros);

                $row.find('td:eq(1)').append($input);

                return $input;
            }
            row_create_presets = function($row, product_id) {
                $presets = create_product_presets(product_id);
                // $presets.change(update_row_macros);
                $row.find('td:eq(2)').append($presets);
            }
        }



        jQuery(function($){
            $('.chosen').chosen({search_contains: true});
            // convert saved items to Product object on load
            $('tr.saved').each(function(i, e){
                var macros = $(e).data('macros');
                var product = new Product(macros.calories, macros.protein, macros.carbs, macros.fat);
                $(e).data('macros', product);
            });

            $('.amount,.presets').live('change', update_row_macros);
            $('#day_preset').change(update_totals);

            $('select[name="product"]').change(function() {
                var $option, $row, $values, macros_string, macros, product_id;


                $option = $(this).find('option:selected');
                product_id = $option.data('id');
                $option.removeAttr('selected');

                $row = build_row(product_id);

                $row = row_set_name($row, $option.text());

                $values = row_create_amount($row);

                row_create_presets($row, product_id);

                macros_string = $option.data('macros').split(',');
                $.each(macros_string, function(i, v) {
                    macros_string[i] = parseFloat(v);
                });
                console.log('macros', macros_string);

                // create instance with args as an array
                var macros = new (Product.bind.apply(Product, [Product].concat(macros_string)));
                row_set_macros($row, macros);

                var product_list = get_product_list();
                product_list.append($row);

                $values.focus();
                update_row_macros.apply($values);
            });
        });
    </script>
</body>
</html>