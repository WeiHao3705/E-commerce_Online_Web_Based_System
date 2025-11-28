<?php
// Helper functions file

function html_escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * HTML Helper Functions
 * Following web programming conventions for reusable HTML components
 */

/**
 * Generate a text input field
 * @param string $name Input name attribute
 * @param string $id Input id attribute
 * @param string $value Input value
 * @param array $options Additional options (placeholder, required, class, etc.)
 * @return string HTML input element
 */
function input_text($name, $id = '', $value = '', $options = []) {
    $id = $id ?: $name;
    $value = html_escape($value);
    $placeholder = isset($options['placeholder']) ? html_escape($options['placeholder']) : '';
    $required = isset($options['required']) && $options['required'] ? 'required' : '';
    $class = isset($options['class']) ? html_escape($options['class']) : 'form-control';
    $autocomplete = isset($options['autocomplete']) ? 'autocomplete="' . html_escape($options['autocomplete']) . '"' : '';
    $type = isset($options['type']) ? html_escape($options['type']) : 'text';
    $maxlength = isset($options['maxlength']) ? 'maxlength="' . (int)$options['maxlength'] . '"' : '';
    $pattern = isset($options['pattern']) ? 'pattern="' . html_escape($options['pattern']) . '"' : '';
    
    return sprintf(
        '<input type="%s" id="%s" name="%s" class="%s" value="%s" placeholder="%s" %s %s %s %s %s>',
        $type,
        $id,
        $name,
        $class,
        $value,
        $placeholder,
        $required,
        $autocomplete,
        $maxlength,
        $pattern,
        isset($options['readonly']) && $options['readonly'] ? 'readonly' : ''
    );
}

/**
 * Generate a number input field
 * @param string $name Input name attribute
 * @param string $id Input id attribute
 * @param string $value Input value
 * @param array $options Additional options (placeholder, required, min, max, step, etc.)
 * @return string HTML input element
 */
function input_number($name, $id = '', $value = '', $options = []) {
    $id = $id ?: $name;
    $value = html_escape($value);
    $placeholder = isset($options['placeholder']) ? html_escape($options['placeholder']) : '';
    $required = isset($options['required']) && $options['required'] ? 'required' : '';
    $class = isset($options['class']) ? html_escape($options['class']) : 'form-control';
    $min = isset($options['min']) ? 'min="' . html_escape($options['min']) . '"' : '';
    $max = isset($options['max']) ? 'max="' . html_escape($options['max']) . '"' : '';
    $step = isset($options['step']) ? 'step="' . html_escape($options['step']) . '"' : '';
    
    return sprintf(
        '<input type="number" id="%s" name="%s" class="%s" value="%s" placeholder="%s" %s %s %s %s>',
        $id,
        $name,
        $class,
        $value,
        $placeholder,
        $required,
        $min,
        $max,
        $step
    );
}

/**
 * Generate a date input field
 * @param string $name Input name attribute
 * @param string $id Input id attribute
 * @param string $value Input value (YYYY-MM-DD format)
 * @param array $options Additional options (required, class, etc.)
 * @return string HTML input element
 */
function input_date($name, $id = '', $value = '', $options = []) {
    $id = $id ?: $name;
    $value = html_escape($value);
    $required = isset($options['required']) && $options['required'] ? 'required' : '';
    $class = isset($options['class']) ? html_escape($options['class']) : 'form-control';
    
    return sprintf(
        '<input type="date" id="%s" name="%s" class="%s" value="%s" %s>',
        $id,
        $name,
        $class,
        $value,
        $required
    );
}

/**
 * Generate a textarea field
 * @param string $name Input name attribute
 * @param string $id Input id attribute
 * @param string $value Input value
 * @param array $options Additional options (placeholder, required, rows, class, etc.)
 * @return string HTML textarea element
 */
function input_textarea($name, $id = '', $value = '', $options = []) {
    $id = $id ?: $name;
    $value = html_escape($value);
    $placeholder = isset($options['placeholder']) ? html_escape($options['placeholder']) : '';
    $required = isset($options['required']) && $options['required'] ? 'required' : '';
    $class = isset($options['class']) ? html_escape($options['class']) : 'form-control';
    $rows = isset($options['rows']) ? (int)$options['rows'] : 4;
    
    return sprintf(
        '<textarea id="%s" name="%s" class="%s" placeholder="%s" rows="%s" %s>%s</textarea>',
        $id,
        $name,
        $class,
        $placeholder,
        $rows,
        $required,
        $value
    );
}

/**
 * Generate a select dropdown field
 * @param string $name Input name attribute
 * @param string $id Input id attribute
 * @param array $options Array of options (value => label or array with 'value' and 'label')
 * @param string $selected Selected value
 * @param array $attributes Additional attributes (required, class, etc.)
 * @return string HTML select element
 */
function input_select($name, $id = '', $options = [], $selected = '', $attributes = []) {
    $id = $id ?: $name;
    $required = isset($attributes['required']) && $attributes['required'] ? 'required' : '';
    $class = isset($attributes['class']) ? html_escape($attributes['class']) : 'form-control';
    $placeholder = isset($attributes['placeholder']) ? html_escape($attributes['placeholder']) : '';
    
    $html = sprintf('<select id="%s" name="%s" class="%s" %s>', $id, $name, $class, $required);
    
    if ($placeholder) {
        $html .= sprintf('<option disabled %s>%s</option>', $selected === '' ? 'selected' : '', $placeholder);
    }
    
    foreach ($options as $key => $option) {
        if (is_array($option)) {
            $value = html_escape($option['value']);
            $label = html_escape($option['label']);
        } else {
            $value = html_escape($key);
            $label = html_escape($option);
        }
        
        $isSelected = ($selected === $value || $selected === $key) ? 'selected' : '';
        $html .= sprintf('<option value="%s" %s>%s</option>', $value, $isSelected, $label);
    }
    
    $html .= '</select>';
    return $html;
}

/**
 * Generate a hidden input field
 * @param string $name Input name attribute
 * @param string $value Input value
 * @return string HTML input element
 */
function input_hidden($name, $value) {
    return sprintf('<input type="hidden" name="%s" value="%s">', html_escape($name), html_escape($value));
}

/**
 * Generate error message display
 * @param array|string $errors Error message(s)
 * @param string $class CSS class for error container
 * @return string HTML error message element
 */
function display_errors($errors, $class = 'error-messages') {
    if (empty($errors)) {
        return '';
    }
    
    if (is_string($errors)) {
        $errors = [$errors];
    }
    
    $html = '<div class="' . html_escape($class) . '">';
    $html .= '<ul>';
    foreach ($errors as $error) {
        $html .= '<li>' . html_escape($error) . '</li>';
    }
    $html .= '</ul>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate success message display
 * @param string $message Success message
 * @param string $class CSS class for success container
 * @return string HTML success message element
 */
function display_success($message, $class = 'success-message') {
    if (empty($message)) {
        return '';
    }
    
    return '<div class="' . html_escape($class) . '">' . html_escape($message) . '</div>';
}

/**
 * Generate a label element
 * @param string $for ID of the associated input
 * @param string $text Label text
 * @param array $options Additional options (class, required indicator, etc.)
 * @return string HTML label element
 */
function label($for, $text, $options = []) {
    $class = isset($options['class']) ? ' class="' . html_escape($options['class']) . '"' : '';
    $required = isset($options['required']) && $options['required'] ? ' <span class="required">*</span>' : '';
    
    return sprintf('<label for="%s"%s>%s%s</label>', html_escape($for), $class, html_escape($text), $required);
}