<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="UTF-8">
    <meta name="description" content="Anime">
    <meta name="keywords" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Carss</title>

    <?php wp_head(); ?>

</head>

<body>

    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="<?php bloginfo('url') ?>"><?php bloginfo('name') ?><span>.</span></a>
            </div>


            <ul class="nav-links">
                <?php wp_nav_menu(array(
                    'theme_location' => 'top_nav',
                    'container' => null,
                    'menu_class' => 'nav',
                    'menu_id' => 'nav',
                )) ?>
            </ul>
        </nav>
    </header>