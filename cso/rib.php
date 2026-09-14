<?php include('includes/header.php'); ?>
<?php include('../includes/session.php');?>
<body>

    <?php include('includes/navbar.php')?>
    <?php include('includes/right_sidebar.php')?>
    <?php include('includes/left_sidebar.php')?>

    <div class="mobile-menu-overlay"></div>
    <div class="main-container">
        <div class="pd-ltr-20 xs-pd-20-10">
            <div class="min-height-200px">
                <div class="page-header">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="title">
                                <h4>RIB</h4>
                            </div>
                            <nav aria-label="breadcrumb" role="navigation">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">RIB</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>

                <div class="pd-20 card-box mb-30">
                    <h5 class="mb-20">Générateur de RIB</h5>
                    <p>Entrez le numéro de compte puis cliquez sur <strong>Générer</strong>. La clé RIB est récupérée automatiquement depuis Flexcube / la base de données.</p>

                    <style>
                        .rib-search-divider { margin: 22px 0; border-top: 1px solid #e5e5e5; }
                        .rib-name-results { display: none; margin-top: 18px; }
                        .rib-name-results.is-visible { display: block; }
                        .rib-name-results table { width: 100%; }
                        .rib-name-results tbody tr { cursor: pointer; }
                        .rib-name-results tbody tr:hover { background: #eef6fb; }
                        .rib-name-results .result-hint { font-size: 12px; color: #666; margin-bottom: 8px; }
                        @media (max-width: 576px) {
                            .rib-name-results { overflow-x: auto; }
                            .rib-name-results table { min-width: 560px; }
                        }
                    </style>

                    <form id="rib-form" class="mt-20">
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Numéro de compte</label>
                            <div class="col-sm-6">
                                <input type="text" id="account-number" name="account" class="form-control" autocomplete="off" placeholder="Saisir le numéro de compte" required>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-sm-8">
                                <button type="submit" class="btn btn-primary" id="generate-btn">Générer</button>
                                <button type="button" id="clear-btn" class="btn btn-light">Effacer</button>
                            </div>
                        </div>
                    </form>

                    <div class="rib-search-divider"></div>
                    <form id="rib-name-form">
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label" for="account-name">Nom du client</label>
                            <div class="col-sm-6">
                                <input type="search" id="account-name" class="form-control" autocomplete="off" placeholder="Rechercher par Nom Client">
                            </div>
                            <div class="col-sm-4 mt-2 mt-sm-0">
                                <button type="submit" class="btn btn-outline-primary" id="search-name-btn">Rechercher</button>
                            </div>
                        </div>
                    </form>

                    <div id="rib-name-results" class="rib-name-results">
                        <div class="result-hint">Double-cliquez sur le bon client pour remplir le formulaire RIB.</div>
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Intitulé du compte</th>
                                    <th>Numéro de compte</th>
                                    <th>Type de compte</th>
                                    <th>Date de naissance</th>
                                    <th>Agence</th>
                                </tr>
                            </thead>
                            <tbody id="rib-name-results-body"></tbody>
                        </table>
                    </div>

                    <script>
                        (function(){
                            var form = document.getElementById('rib-form');
                            var nameForm = document.getElementById('rib-name-form');
                            var nameInput = document.getElementById('account-name');
                            var resultsBox = document.getElementById('rib-name-results');
                            var resultsBody = document.getElementById('rib-name-results-body');
                            var clear = document.getElementById('clear-btn');
                            var generateBtn = document.getElementById('generate-btn');
                            
                            form.addEventListener('submit', function(ev){
                                ev.preventDefault();
                                var acct = document.getElementById('account-number').value.trim();
                                if(!acct){
                                    alert('Veuillez saisir un numéro de compte.');
                                    return;
                                }
                                
                                // Rediriger vers le formulaire RIB (le loader apparaîtra là-bas)
                                var url = 'rib_ecobank.html?account=' + encodeURIComponent(acct);
                                window.location = url;
                            });
                            clear.addEventListener('click', function(){
                                document.getElementById('account-number').value = '';
                                generateBtn.disabled = false;
                            });

                            nameForm.addEventListener('submit', function(ev){
                                ev.preventDefault();
                                var name = nameInput.value.trim();
                                if(name.length < 2){
                                    alert('Veuillez saisir au moins deux caractères du nom du client.');
                                    return;
                                }

                                resultsBody.innerHTML = '<tr><td colspan="5">Recherche en cours...</td></tr>';
                                resultsBox.classList.add('is-visible');

                                fetch('rib_search_name.php?name=' + encodeURIComponent(name), {
                                    headers: { 'Accept': 'application/json' }
                                })
                                .then(function(response){ return response.json(); })
                                .then(function(payload){
                                    resultsBody.innerHTML = '';
                                    if(payload.status !== 'ok' || !payload.results || payload.results.length === 0){
                                        resultsBody.innerHTML = '<tr><td colspan="5">Aucun compte trouvé pour cet intitulé.</td></tr>';
                                        return;
                                    }

                                    payload.results.forEach(function(result){
                                        var row = document.createElement('tr');
                                        row.innerHTML = '<td>' + escapeHtml(result.account_title) + '</td>'
                                            + '<td>' + escapeHtml(result.account_number) + '</td>'
                                            + '<td>' + escapeHtml(result.account_type) + '</td>'
                                            + '<td>' + escapeHtml(result.date_of_birth) + '</td>'
                                            + '<td>' + escapeHtml(result.branch_code) + '</td>';
                                        row.addEventListener('dblclick', function(){
                                            document.getElementById('account-number').value = result.account_number || '';
                                            resultsBox.classList.remove('is-visible');
                                            nameInput.value = result.account_title || '';
                                        });
                                        resultsBody.appendChild(row);
                                    });
                                })
                                .catch(function(){
                                    resultsBody.innerHTML = '<tr><td colspan="5">La recherche est indisponible.</td></tr>';
                                });
                            });

                            function escapeHtml(value){
                                return String(value || '').replace(/[&<>"']/g, function(character){
                                    return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[character];
                                });
                            }
                        })();
                    </script>
                </div>
            </div>
        </div>
    </div>

    <?php include('includes/scriptJs.php')?>
</body>
</html>