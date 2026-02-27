<?php
/**
 * Plugin Name: Woo Raffle Number Selector
 * Plugin URI: https://github.com/GGRDEV/woo-raffle-number-selector
 * Description: Adds raffle functionality to WooCommerce products with number selection, cart validation, dynamic pricing, and automatic number locking.
 * Version: 1.0.0
 * Author: Gonzalo Rolon
 * Author URI: https://ggrdev.site
 * License: GPL2
 */

// 1. Agregar una casilla para indicar que el producto es un sorteo
add_action('woocommerce_product_options_general_product_data', 'agregar_casilla_sorteo');
function agregar_casilla_sorteo() {
    echo '<div class="options_group">';
    woocommerce_wp_checkbox([
        'id'          => '_es_sorteo',
        'label'       => __('¿Es un sorteo?', 'woocommerce'),
        'desc_tip'    => true,
        'description' => __('Marca esta casilla si el producto es un sorteo.', 'woocommerce'),
    ]);

    woocommerce_wp_text_input([
        'id'          => '_rango_numeros',
        'label'       => __('Rango de números (ej: 001-100)', 'woocommerce'),
        'placeholder' => '001-100',
        'desc_tip'    => true,
        'description' => __('Define el rango de números disponibles para este sorteo.', 'woocommerce'),
        'type'        => 'text',
    ]);
    echo '</div>';
}

// 2. Guardar los campos adicionales del producto
add_action('woocommerce_process_product_meta', 'guardar_campos_sorteo');
function guardar_campos_sorteo($post_id) {
    $es_sorteo = isset($_POST['_es_sorteo']) ? 'yes' : 'no';
    update_post_meta($post_id, '_es_sorteo', $es_sorteo);

    if (isset($_POST['_rango_numeros'])) {
        update_post_meta($post_id, '_rango_numeros', sanitize_text_field($_POST['_rango_numeros']));
    }
}

// 3. Mostrar el botón para abrir el popup
add_action('woocommerce_before_add_to_cart_button', 'mostrar_boton_popup_sorteos');
function mostrar_boton_popup_sorteos() {
    global $product;

    $es_sorteo = get_post_meta($product->get_id(), '_es_sorteo', true);
    if ($es_sorteo !== 'yes') return;

    echo '<button type="button" id="abrir-popup-numeros" class="button" style="margin-bottom:20px;">Seleccionar números</button>';

    echo '<div id="popup-numeros" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); width:90%; max-width:600px; background:white; padding:20px; border:1px solid #ccc; box-shadow:0 5px 15px rgba(0,0,0,0.5); z-index:1000;">';
    echo '<h3>Selecciona tus números</h3>';
    echo '<div id="numeros-container" style="max-height:400px; overflow-y:auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(50px, 1fr)); gap: 10px; padding: 10px;">';

    $rango = get_post_meta($product->get_id(), '_rango_numeros', true);
    if (!$rango || strpos($rango, '-') === false) {
        echo '<p>No hay números disponibles para este sorteo.</p>';
    } else {
        list($inicio, $fin) = explode('-', $rango);
        $inicio = intval($inicio);
        $fin = intval($fin);

        $comprados = get_post_meta($product->get_id(), '_numeros_comprados', true) ?: [];

        for ($i = $inicio; $i <= $fin; $i++) {
            $numero = str_pad($i, strlen($fin), '0', STR_PAD_LEFT);
            $disabled = in_array($numero, $comprados) ? 'disabled' : '';
            $tachado = $disabled ? 'text-decoration: line-through; color: red;' : '';
            echo '<label style="cursor: pointer;">';
            echo '<input type="checkbox" name="numeros_sorteo[]" value="' . esc_attr($numero) . '" ' . $disabled . ' style="margin-right: 5px;">';
            echo '<span style="' . $tachado . '">' . esc_html($numero) . '</span>';
            echo '</label>';
        }
    }

    echo '</div>';
    echo '<button type="button" id="cerrar-popup-numeros" class="button" style="margin-top:10px; margin-right:10px;">Cerrar</button>';
    echo '<button type="button" id="confirmar-numeros" class="button button-primary" style="margin-top:10px;">Añadir números</button>';
    echo '</div>';

    echo '<div id="fondo-popup-numeros" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999;"></div>';
}

// 4. Scripts para manejar el popup
add_action('wp_footer', 'popup_sorteos_scripts');
function popup_sorteos_scripts() { ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const abrirPopup = document.getElementById('abrir-popup-numeros');
            const cerrarPopup = document.getElementById('cerrar-popup-numeros');
            const confirmarNumeros = document.getElementById('confirmar-numeros');
            const popup = document.getElementById('popup-numeros');
            const fondo = document.getElementById('fondo-popup-numeros');
            const numerosContainer = document.getElementById('numeros-container');

            if (abrirPopup) abrirPopup.addEventListener('click', () => {
                popup.style.display = 'block'; fondo.style.display = 'block';
            });
            if (cerrarPopup) cerrarPopup.addEventListener('click', () => {
                popup.style.display = 'none'; fondo.style.display = 'none';
            });
            if (fondo) fondo.addEventListener('click', () => {
                popup.style.display = 'none'; fondo.style.display = 'none';
            });

            if (confirmarNumeros) confirmarNumeros.addEventListener('click', () => {
                const checkboxes = numerosContainer.querySelectorAll('input[name="numeros_sorteo[]"]:checked');
                const seleccionados = Array.from(checkboxes).map(cb => cb.value);
                if (seleccionados.length > 0) {
                    alert('Has seleccionado los números: ' + seleccionados.join(', '));
                    let inputSeleccionados = document.querySelector('input[name="numeros_sorteo_seleccionados"]');
                    if (!inputSeleccionados) {
                        inputSeleccionados = document.createElement('input');
                        inputSeleccionados.type = 'hidden';
                        inputSeleccionados.name = 'numeros_sorteo_seleccionados';
                        document.querySelector('form.cart').appendChild(inputSeleccionados);
                    }
                    inputSeleccionados.value = seleccionados.join(',');
                    popup.style.display = 'none';
                    fondo.style.display = 'none';
                } else {
                    alert('Por favor selecciona al menos un número.');
                }
            });
        });
    </script>
<?php } 











// 5. Guardar los números seleccionados en el carrito
add_filter('woocommerce_add_cart_item_data', 'guardar_numeros_en_carrito', 10, 2);
function guardar_numeros_en_carrito($cart_item_data, $product_id) {
    if (isset($_POST['numeros_sorteo_seleccionados'])) {
        $cart_item_data['numeros_sorteo'] = array_map('sanitize_text_field', explode(',', $_POST['numeros_sorteo_seleccionados']));
        $cart_item_data['unique_key'] = md5(uniqid());
    }
    return $cart_item_data;
}

// ✅ 6. Ajustar el precio del producto en función de los números seleccionados (versión corregida)
add_filter('woocommerce_before_calculate_totals', 'ajustar_precio_por_numeros', 10, 1);
function ajustar_precio_por_numeros($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        if (isset($cart_item['numeros_sorteo']) && is_array($cart_item['numeros_sorteo'])) {
            $cantidad_numeros = count($cart_item['numeros_sorteo']);

            // Guardar el precio original solo la primera vez
            if (!isset($cart_item['precio_original'])) {
                $cart->cart_contents[$cart_item_key]['precio_original'] = $cart_item['data']->get_price();
            }

            $precio_original = $cart->cart_contents[$cart_item_key]['precio_original'];
            $nuevo_precio = $precio_original * $cantidad_numeros;

            $cart_item['data']->set_price($nuevo_precio);
        }
    }
}





// // 7. Mostrar los números seleccionados en el carrito y checkout
add_filter('woocommerce_get_item_data', 'mostrar_numeros_en_carrito', 10, 2);
function mostrar_numeros_en_carrito($item_data, $cart_item) {
    if (isset($cart_item['numeros_sorteo'])) {
        $item_data[] = [
            'name' => __('Números seleccionados', 'woocommerce'),
            'value' => implode(', ', $cart_item['numeros_sorteo']),
        ];
    }
    return $item_data;
}

// 8. Guardar los números en los metadatos del pedido
add_action('woocommerce_checkout_create_order_line_item', 'guardar_numeros_en_pedido', 10, 4);
function guardar_numeros_en_pedido($item, $cart_item_key, $values, $order) {
    if (isset($values['numeros_sorteo'])) {
        $item->add_meta_data('Números seleccionados', implode(', ', $values['numeros_sorteo']));
    }
}

// 9. Actualizar los números comprados al completar un pedido
// 🔁 Bloquear números al pasar a PROCESANDO o COMPLETADO
add_action('woocommerce_order_status_processing', 'bloquear_numeros_pedido');
add_action('woocommerce_order_status_completed', 'bloquear_numeros_pedido');

function bloquear_numeros_pedido($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        $es_sorteo = get_post_meta($product_id, '_es_sorteo', true);

        if ($es_sorteo === 'yes') {
            $numeros_comprados = get_post_meta($product_id, '_numeros_comprados', true) ?: [];
            $numeros_seleccionados = $item->get_meta('Números seleccionados', true);

            if (!empty($numeros_seleccionados)) {
                $numeros_seleccionados_array = explode(', ', $numeros_seleccionados);
                $numeros_comprados = array_unique(array_merge($numeros_comprados, $numeros_seleccionados_array));
                update_post_meta($product_id, '_numeros_comprados', $numeros_comprados);
            }
        }
    }
}

// 🔓 Liberar números si el pedido se CANCELA, FALLA o se REEMBOLSA
add_action('woocommerce_order_status_cancelled', 'liberar_numeros_pedido');
add_action('woocommerce_order_status_failed', 'liberar_numeros_pedido');
add_action('woocommerce_order_status_refunded', 'liberar_numeros_pedido');

function liberar_numeros_pedido($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        $es_sorteo = get_post_meta($product_id, '_es_sorteo', true);

        if ($es_sorteo === 'yes') {
            $numeros_comprados = get_post_meta($product_id, '_numeros_comprados', true) ?: [];
            $numeros_seleccionados = $item->get_meta('Números seleccionados', true);

            if (!empty($numeros_seleccionados)) {
                $numeros_seleccionados_array = explode(', ', $numeros_seleccionados);

                // Quitar los números de este pedido del array general
                $numeros_actualizados = array_diff($numeros_comprados, $numeros_seleccionados_array);
                update_post_meta($product_id, '_numeros_comprados', array_values($numeros_actualizados));
            }
        }
    }
}


// 10. Reemplazar el botón "Añadir al carrito" para productos de sorteo
add_filter('woocommerce_loop_add_to_cart_link', 'reemplazar_boton_tienda_para_sorteos', 10, 2);
function reemplazar_boton_tienda_para_sorteos($button, $product) {
    $es_sorteo = get_post_meta($product->get_id(), '_es_sorteo', true);
    if ($es_sorteo === 'yes') {
        $url = get_permalink($product->get_id());
        $button = '<a href="' . esc_url($url) . '" class="button select-numbers">' . __('Seleccionar números', 'woocommerce') . '</a>';
    }
    return $button;
}

// 11. Validar selección antes de añadir al carrito
add_filter('woocommerce_add_to_cart_validation', 'validar_seleccion_de_numeros', 10, 3);
function validar_seleccion_de_numeros($passed, $product_id, $quantity) {
    $es_sorteo = get_post_meta($product_id, '_es_sorteo', true);
    if ($es_sorteo === 'yes' && empty($_POST['numeros_sorteo_seleccionados'])) {
        wc_add_notice(__('Por favor, selecciona al menos un número para este sorteo antes de añadir al carrito.', 'woocommerce'), 'error');
        $passed = false;
    }
    return $passed;
}

// =========================
// 🔁 Botón "Reiniciar rifa"
// =========================
add_action('woocommerce_product_options_general_product_data', 'agregar_boton_reiniciar_rifa');
function agregar_boton_reiniciar_rifa() {
    global $post;
    $es_sorteo = get_post_meta($post->ID, '_es_sorteo', true);
    if ($es_sorteo === 'yes') {
        $url = wp_nonce_url(
            add_query_arg([
                'action'     => 'reiniciar_rifa',
                'product_id' => $post->ID,
            ]),
            'reiniciar_rifa_' . $post->ID
        );
        echo '<div class="options_group">';
        echo '<a href="' . esc_url($url) . '" class="button button-secondary" style="background:#d63638;color:#fff;border:none;">🗑️ Reiniciar rifa (borrar números comprados)</a>';
        echo '<p class="description">Haz clic aquí para reiniciar los números del sorteo y dejarlo vacío.</p>';
        echo '</div>';
    }
}

// Acción que borra los números comprados
add_action('admin_init', 'accion_reiniciar_rifa');
function accion_reiniciar_rifa() {
    if (isset($_GET['action'], $_GET['product_id']) && $_GET['action'] === 'reiniciar_rifa' && current_user_can('edit_products')) {
        $product_id = intval($_GET['product_id']);
        if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'reiniciar_rifa_' . $product_id)) {
            wp_die('Error de seguridad.');
        }
        delete_post_meta($product_id, '_numeros_comprados');
        wp_redirect(add_query_arg(['rifa_reiniciada' => 'true'], get_edit_post_link($product_id, '')));
        exit;
    }
}

// Mostrar mensaje de confirmación
add_action('admin_notices', 'mensaje_reiniciar_rifa_exitoso');
function mensaje_reiniciar_rifa_exitoso() {
    if (isset($_GET['rifa_reiniciada']) && $_GET['rifa_reiniciada'] === 'true') {
        echo '<div class="notice notice-success is-dismissible"><p>✅ La rifa se ha reiniciado correctamente. Todos los números fueron liberados.</p></div>';
    }
}


// Bloquear botones si ya existe una rifa en el carrito
add_action('wp_head', 'estilos_bloqueo_sorteo');
function estilos_bloqueo_sorteo() {
    if (!WC()->cart) return;

    $hay_sorteo = false;
    foreach (WC()->cart->get_cart() as $cart_item) {
        if (get_post_meta($cart_item['product_id'], '_es_sorteo', true) === 'yes') {
            $hay_sorteo = true;
            break;
        }
    }

    if ($hay_sorteo) {
        echo '<style>
            /* Deshabilitar botón de selección personalizado */
            #abrir-popup-numeros {
                background-color: #ccc !important;
                cursor: not-allowed !important;
                pointer-events: none;
            }
            /* Deshabilitar botones de añadir/comprar nativos */
            .single_add_to_cart_button, .buy_now_button {
                display: none !important;
            }
        </style>';
    }
}

// Cambiar el texto del botón de apertura del popup
add_filter('gettext', 'cambiar_texto_boton_popup', 20, 3);
function cambiar_texto_boton_popup($translated_text, $text, $domain) {
    if (!is_admin() && $text === 'Seleccionar números') {
        if (!WC()->cart) return $translated_text;
        
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (get_post_meta($cart_item['product_id'], '_es_sorteo', true) === 'yes') {
                return 'Rifa ya en el carrito';
            }
        }
    }
    return $translated_text;
}

add_filter('woocommerce_add_to_cart_validation', 'validar_un_solo_sorteo', 10, 2);
function validar_un_solo_sorteo($passed, $product_id) {
    $es_sorteo = get_post_meta($product_id, '_es_sorteo', true);
    if ($es_sorteo !== 'yes') return $passed;

    foreach (WC()->cart->get_cart() as $cart_item) {
        if (get_post_meta($cart_item['product_id'], '_es_sorteo', true) === 'yes') {
            wc_add_notice('Ya tienes una rifa en el carrito. Elimínala para elegir números nuevos.', 'error');
            return false;
        }
    }
    return $passed;
}

<?