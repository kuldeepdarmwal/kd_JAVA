	<script type="text/javascript" src="https://code.jquery.com/jquery-2.1.1.min.js"></script>
	<script src="/libraries/external/select2/select2.js"></script>

    <script src="/public/app/lib/es6-shim.min.js"></script>
    <script src="/public/app/lib/angular2-polyfills.js"></script>
    <script src="/public/app/lib/traceur-runtime.js"></script>
    <script src="/public/app/lib/system.js"></script>
    <script src="/public/app/lib/Reflect.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.97.6/js/materialize.min.js"></script>
    <script src="/public/app/lib/mustache.min.js"></script>

    <script>
        System.config({
            defaultJSExtensions: true, 
            baseURL: '/public/app',
            packages: {
                "angular2-materialize": {
                    "defaultExtension": "js"
                },
                "mustache": {
                    "defaultExtension": "js"
                }
            },
            map: {
                'angular2-materialize': 'lib/materialize-directive',
                'mustache': 'lib/mustache'
            }
        });
    </script>

    <script src="/public/app/lib/angular2.dev.js"></script>
    <script src="/public/app/lib/http.dev.js"></script>
    <script src="/public/app/lib/Rx.js"></script>

    <input type="hidden" value="<?php echo $proposal_id; ?>" id="proposal_id"/>

    <script>
        System.import('proposal-builder')
            .catch(console.log.bind(console));
    </script>

</body>
</html>