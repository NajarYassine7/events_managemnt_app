<?php
/**
 * Fenêtre de confirmation de suppression (partagée).
 * À inclure UNE fois par page, juste avant </main>.
 * Les formulaires concernés portent la classe "js-confirm-delete"
 * et un attribut data-title avec le nom de l'événement.
 */
?>
<dialog id="confirm-dialog" class="confirm-dialog">
    <h2>Supprimer cet événement ?</h2>
    <p id="confirm-dialog-text">Cette action est définitive.</p>
    <div class="btn-row">
        <button type="button" id="confirm-cancel" class="btn-nav-outline">Annuler</button>
        <button type="button" id="confirm-ok" class="btn-danger">Supprimer</button>
    </div>
</dialog>

<script>
(function () {
    var dialog = document.getElementById('confirm-dialog');
    var text = document.getElementById('confirm-dialog-text');
    var pending = null;                     // le formulaire en attente

    document.querySelectorAll('form.js-confirm-delete').forEach(function (form) {
        form.addEventListener('submit', function (ev) {
            ev.preventDefault();            // on bloque l'envoi...
            pending = form;
            text.textContent = '« ' + (form.dataset.title || 'Cet événement')
                             + ' » sera supprimé définitivement.';
            dialog.showModal();             // ...et on ouvre la fenêtre
        });
    });

    document.getElementById('confirm-cancel').addEventListener('click', function () {
        pending = null;
        dialog.close();
    });
    document.getElementById('confirm-ok').addEventListener('click', function () {
        if (pending) { pending.submit(); }  // envoi réel (POST + jeton CSRF)
    });
})();
</script>