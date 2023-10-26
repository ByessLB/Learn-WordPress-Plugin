<?php

/**
 * Plugin Name: Simple Contact Form
 * Description: Simple contact form tutorial
 * Author: @MlByeSs
 * Author URI: https://wisepanda.fr
 * Version: 1.0.0
 * Text Domain: simple-contact-form
 *
 */

// Basic Security

if (!defined('ABSPATH')) { //ABSPATH = Chemin absolu de... : la c'est pour notre site WP
    echo "What are you trying to do ?";
    exit;
}

/**
 * Create a Custom Post Type
 *
 */

// Procedural

/*
function create_new_simple_form()
{
// register_post_type( $post_type:string, $args:array|string ) : cette function provient de WP , espace dev's
register_post_type('simple_contact_form', array(
'public' => true, // Indique que le formulaire est public
'has_archive' => true, // Permet d'activer l'archivage du formulaire
'supports' => array('title'), // D'accepter | supporter uniquement le champ de titre
'exclude_from_serch' => true, //Exclut le formulaire des moteurs de recherches
'publicly_queryable' => false, // Empêche les requêtes publiques
'capability_type' => 'post', // Défini les droits nécessaire pour éditer et gérer le formulaire
'labels' => array(
'name' => 'Contact Form',
'singular_name' => 'Contact Form Entry'
// Définit les libellés pour le formulaire, tels que le nom au pluriel et au singulier
),
'menu_icon' => 'dashicons-media-text'
));
}

add_action(
'init',
'create_new_simple_form'
);
 */

// POO

class SimpleContactForm
{
    public function __construct()
    {
        //create custom post type
        add_action('init', array($this, 'create_custom_post_type')); // ici, "$this" veut dire : appel "cette" fonction qui est dans l'"objet"

        // Add assets (js, css)
        add_action('wp_enqueue_scripts', array($this, 'load_assets'));

        // Add Shortcode
        add_shortcode('contact-form', array($this, 'load_shortcode'));

        // Load javascript
        add_action('wp_footer', array($this, 'load_scripts'));

        // Register Rest API
        add_action('rest_api_init', array($this, 'register_rest_api'));
    }

    // Création de notre formulaire, avec tout ses paramètres
    public function create_custom_post_type()
    {
        $args = array(
            'public' => true, // Indique que le formulaire est public
            'has_archive' => true, // Permet d'activer l'archivage du formulaire
            'supports' => array('title'), // D'accepter | supporter uniquement le champ de titre
            'exclude_from_serch' => true, //Exclut le formulaire des moteurs de recherches
            'publicly_queryable' => false, // Empêche les requêtes publiques
            'capability_type' => 'post', // Défini les droits nécessaire pour éditer et gérer le formulaire
            'labels' => array(
                'name' => 'Contact Form',
                'singular_name' => 'Contact Form Entry',
                // Définit les libellés pour le formulaire, tels que le nom au pluriel et au singulier
            ),
            'menu_icon' => 'dashicons-media-text',
        );

        register_post_type('simple_contact_form', $args);
    }

    // Permet de charger nos assets (css, js, etc...) sur les pages
    public function load_assets()
    {
        // On recherche sur la doc de wordpress/dev tout ce qui se rapproche au "style" (css)
        // là, nous allons choisir : wp_enqueue_style
        wp_enqueue_style(
            'simple-contact-form', // nom de la feuille
            plugin_dir_url(__FILE__) . 'assets/css/simple-contact-form.css', // rentre le chemin dans le plugin
            array(), // installation d'un tableau qui récupèrera nos fichier (bootstrap, etc...)
            1, // version de notre extension
            'all' // actif sur tout nos "devise"
        );

        wp_enqueue_script(
            'simple-contact-form',
            plugin_dir_url(__FILE__) . 'assets/js/simple-contact-form.js',
            array('jquery'),
            1,
            true
        );
    }

    // Création d'un shortcode pour une meilleur intégration de notre formulaire
    public function load_shortcode()
    {
        ob_start();
        ?>

<div class="container simple-contact-form">
    <h1 class="display-2 color-red">Restons en contact</h1>
    <p class="lead"> Veuillez remplir le formulaire</p>
    <div class="divider"></div>

    <form id="simple-contact-form__form" method="POST">
        <div class="mb-3">
            <label for="nom">Nom :</label>
            <input class="form-control" type="text" name="nom" placeholder="Nom" required>
        </div>

        <div class="mb-3">
            <label for="email">E-mail :</label>
            <input class="form-control" type="email" name="email" placeholder="name@exemple.com" required>
        </div>

        <div class="mb-3">
            <label for="objet">Objet :</label>
            <input class="form-control" type="text" name="objet" placeholder="Objet" required>
        </div>

        <div class="mb-3">
            <label for="message">Message :</label>
            <textarea class="form-control" type="text" name="message" rows="5" placeholder=" Message"
                required></textarea>
        </div>

        <div class="from-check mb-3">
            <input class="form-check-input" type="checkbox">
            <label class="form-check-label" for="terms">Terms & conditions</label>
        </div>

        <div class="d-flex justify-content-end ">
            <button type="submit" class="btn btn-outline-success btn-block shadow"> Envoyez</button>
        </div>

    </form>
</div>

<?php

        return ob_get_clean();
    }

    public function load_scripts()
    {?>

<script>
const NONCE = '<?php echo wp_create_nonce('wp_rest'); ?>';
console.log(NONCE);
// const GET_FORM = document.getElementById('simple-contact-form__form');
// console.log(GET_FORM);// exemple comment récupérer des éléments dans le DOM

// alert('ça fonctionne bien !'); // teste JS (voir si cela fonctionne)
(function($) {
    $('#simple-contact-form__form').submit(function(event) {

        event.preventDefault();
        // alert('Votre message a été bien envoyé !'); // teste jquery (voir si cela fonctionne)

        const form = $(this).serialize();
        console.log(form);

        $.ajax({
            method: 'POST',
            url: '<?php echo get_rest_url(null, 'simple-contact-form/v1/send-email'); ?>',
            headers: {
                'X-WP-Nonce': NONCE
            },
            data: form
        })
    });
})(jQuery)
</script>

<?php
}

// Create REST API endpoint
    public function register_rest_api()
    {
        register_rest_route('simple-contact-form/v1', 'send-email', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_contact_form'),
        ));
    }

    public function handle_contact_form($data)
    {
        // echo 'ce endpoint fonctionne correctement';
        $headers = $data->get_headers(); // affiche les informations du headers
        $params = $data->get_params();

        // Créer un tableau JSON
        /*echo json_encode($headers);*/

        $nonce = $headers['x_wp_nonce'][0];
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            // echo 'This nonce is not correct'; // à afficher s'il y a erreur
            return new WP_REST_response('Message not sent', 422);
        }
        // $message = $_POST['message'];
        // $objet = $_POST['objet'];
        // Add data to POST
        $post_id = wp_insert_post([
            'post_type' => 'simple_contact_form',
            'post_title' => /*$objet*/'demande de contact / contact enquiry',
            // 'post_excerpt' => $message,
            'post_status' => 'publish',
        ]);
        if ($post_id) {
            return new WP_REST_Response('Thank you for your E-mail', 200);
        }
    }

}

new SimpleContactForm;

?>