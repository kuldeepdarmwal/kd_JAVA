<!doctype html>
<html class="no-js">
  <head>
    <meta charset="utf-8">
    <link rel="shortcut icon" href="//s3.amazonaws.com/brandcdn-assets/partners/frequence/favicon.png">
    <title>Frequence</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width">
    <link rel="stylesheet" href="/assets/materialize/css/materialize.min.css"/>
    <link rel="stylesheet" href="/assets/style/screen.css"/>
  </head>  
  <body class="grey lighten-2">
    <header>
        <nav class="top-nav blue">
            <div class="container">
                <div class="nav-wrapper"><a class="page-title">Proposal <?php echo $proposal_id; ?></a></div>
            </div>
        </nav>
        <div class="container"><a href="#" data-activates="nav-mobile" class="button-collapse top-nav full"><i class="mdi-navigation-menu"></i></a></div>
        <ul id="nav-mobile" class="side-nav fixed">
            <li class="logo no-hover">
                <a id="logo-container" href="/" class="brand-logo"><img src="https://s3.amazonaws.com/brandcdn-assets/images/frequence-logo.png"/></a>
            </li>
            <li class="input-field row no-hover">
                <select id="template" class="browser-default">
                    <?php
                        if ($saved_template)
                        {
                            echo '<option value="null">Proposal '.$proposal_id.'</option>';
                        }
                    ?>
                    <?php if (!empty($templates)): ?>
                    <optgroup label="Templates" id="base_templates">
                    <?php
                        foreach($templates as $template)
                        {
                            // TODO v2: something smarter with removing the S3 prefix
                            $friendly_key = str_replace('templates/', '', $template['filename']);
                            echo '<option value="'.$template['id'].'">'.$friendly_key.'</option>';
                        }
                    ?>
                    </optgroup>
                    <?php endif; ?>
                </select>
            </li>
            <li>
                <a id="save_proposal_html" href="#">Save HTML <i class="mdi-content-save right"></i></a>
            </li>  
            <li>
                <?php $pdf_disabled = $saved_template ? '' : 'disabled'; ?>
                <a href="/proposals/<?php echo $proposal_id; ?>/pdf/<?php echo $pdf_title; ?>" target="_blank" <?php echo $pdf_disabled; ?>>Generate PDF <i class="mdi-action-get-app right"></i></a>
            </li>
            <li>
                <a href="#modal1" class="modal-trigger">Upload Template <i class="mdi-file-cloud-upload right"></i></a>
            </li>
        </ul>
    </header>
    <main>
        <iframe id="proposal">Nothing loaded!</iframe>
    </main>
    <div id="modal1" class="modal bottom-sheet">
        <div class="modal-content container">
            <form action="/proposals/upload_template" method="POST" enctype="multipart/form-data" id="template_upload" class="row">
                <div class="file-field input-field col s4">
                    <input type="text" class="file-path" readonly />
                    <div class="btn">
                        <span>File</span>
                        <input type="file" name="template" />
                    </div>
                </div>
                <div class="input-field col s2">
                    <input type="checkbox" name="is_landscape" id="is_landscape" /> <label for="is_landscape">Landscape?</label>
                </div>
                <div class="input-field col s2 right">
                    <button type="submit" class="waves-effect waves-light btn right" style="height:3rem;">Upload</button>
                </div>
            </form>
        </div>
    </div>
    <input id="proposal_id" value="<?php echo $proposal_id; ?>" type="hidden"/>
    <script type="text/javascript" src="/libraries/external/jquery-1.10.2/jquery-1.10.2.min.js"></script>
    <script type="text/javascript" src="/libraries/external/js/jquery-placeholder/jquery.placeholder.js"></script>
    <script type="text/javascript" src="/assets/materialize/js/materialize.min.js"></script>
    <!--script type="text/javascript" src="/vendor/ckeditor/ckeditor/ckeditor.js"></script>
    <script type="text/javascript" src="/vendor/ckeditor/ckeditor/adapters/jquery.js"></script-->
    <script type="text/javascript" src="/assets/js/proposals/proposals.js"></script>
  </body>
</html>