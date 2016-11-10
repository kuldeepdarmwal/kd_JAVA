<!-- notes section -->
<div id="mpq_notes_info_section" class="mpq_section_card card scrollspy">
    <h4 class="card-title grey-text text-darken-1 bold">Notes</h4>
    <div class="card-content">
        <div class="row">
            <div class="input-field col s12">
                <textarea id="mpq_notes_input" class="materialize-textarea"><?php echo empty($notes) ? "" : $notes; ?></textarea>
                <label for="mpq_notes_input">Notes about your Insertion Order</label>
            </div>
        </div>
    </div>
</div>
