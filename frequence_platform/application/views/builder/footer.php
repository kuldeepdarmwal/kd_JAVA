        <script type="text/javascript" src="https://code.jquery.com/jquery-2.1.1.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.97.6/js/materialize.min.js"></script>
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.6/d3.js"></script>
        <script src='/assets/js/dragula.js'></script>
        <script src="/assets/js/mustache/mustache.min.js"></script>
        <script src="/libraries/external/select2/select2.js"></script>
        <script src="/assets/js/mpq/placecomplete.js"></script>
        <script src="/assets/js/perfect_scrollbar/perfect-scrollbar.jquery.min.js"></script>
        <script src="/assets/js/perfect_scrollbar/perfect-scrollbar.min.js"></script>
        <script type="text/javascript" src="//maps.googleapis.com/maps/api/js?libraries=places&key=<?php echo $google_access_token_rfp_io; ?>"></script>
        <script type="text/javascript" src="//www.google.com/jsapi"></script>
        <script type="text/javascript" src="https://cdn.polyfill.io/v2/polyfill.min.js?features=Intl.~locale.en"></script>
        <script src="/libraries/external/momentjs/moment.min.js"></script>
        <script type="text/javascript">
            var allowed_monthly_impressions_to_population_ratio = <?php echo $allowed_monthly_impressions_to_population_ratio; ?>;
        </script>

<?php
if (ENVIRONMENT == "local" || ENVIRONMENT == "localhost-development") :
?>
        <script src="/node_modules/zone.js/dist/zone.js"></script>
        <script src="/node_modules/es6-shim/es6-shim.min.js"></script>
        <script src="/node_modules/reflect-metadata/Reflect.js"></script>

        <script src="../node_modules/systemjs/dist/system.js"></script>
        <script src="/angular/src/systemjs.conf.js"></script>
        <script>
          System.import('/angular/src/tmp/app/rfp/rfp.js')
            .catch(function(err) { console.error(err); });
        </script>

<?php else : ?>

        <script type="text/javascript" src="/angular/build/assets/lib.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>
        <script type="text/javascript" src="/angular/build/assets/rfp.js?v=<?php echo CACHE_BUSTER_VERSION; ?>"></script>

<?php endif; ?>

