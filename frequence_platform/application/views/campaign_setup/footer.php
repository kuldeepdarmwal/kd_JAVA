<script type="text/javascript" src="https://code.jquery.com/jquery-2.1.1.min.js"></script>
<script src="/libraries/external/select2/select2.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.97.6/js/materialize.min.js"></script>
<script src="/assets/js/mpq/placecomplete.js"></script>
<script src="/assets/js/moment/moment.js"></script>
<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?libraries=places&key=AIzaSyAIVzAlGYSW_k3grqeByAiku012dtGfuPs"></script>
<script type="text/javascript" src="//www.google.com/jsapi"></script>
<script type="text/javascript" src="https://cdn.polyfill.io/v2/polyfill.min.js?features=Intl.~locale.en"></script>

<?php
if (ENVIRONMENT == "local" || ENVIRONMENT == "localhost-development") :
?>
        <script src="/node_modules/zone.js/dist/zone.js"></script>
        <script src="/node_modules/es6-shim/es6-shim.min.js"></script>
        <script src="/node_modules/reflect-metadata/Reflect.js"></script>

        <script src="../node_modules/systemjs/dist/system.js"></script>
        <script src="/angular/src/systemjs.conf.js"></script>
        <script>
          System.import('/angular/src/tmp/app/campaign_setup/campaign-setup.js')
            .catch(function(err) { console.error(err); });
        </script>

<?php else : ?>

        <script type="text/javascript" src="/angular/build/assets/lib.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
        <script type="text/javascript" src="/angular/build/assets/campaign-setup.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>

<?php endif; ?>