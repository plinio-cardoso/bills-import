/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/global.scss';
import $ from 'jquery';

$(document).ready(function() {
    $('#form_credit_card').change(function() {
        if ($(this).is(':checked')) {
            $('#form_debit_account').prop('checked', false);
            $('#form_debit_account_option').prop('disabled', true).val('');
            $('#form_credit_card_option').prop('disabled', false);
        } else {
            $('#form_credit_card_option').prop('disabled', true).val('');
        }
    });

    $('#form_debit_account').change(function() {
        if ($(this).is(':checked')) {
            $('#form_credit_card').prop('checked', false);
            $('#form_credit_card_option').prop('disabled', true).val('');
            $('#form_debit_account_option').prop('disabled', false);
        } else {
            $('#form_debit_account_option').prop('disabled', true).val('');
        }
    });
});