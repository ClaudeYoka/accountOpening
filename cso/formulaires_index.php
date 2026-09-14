<?php 
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
?>

<style>
    .formulaires-page {
        padding: 20px;
        background-color: #ffffff;
        min-height: 100vh;
    }

    .formulaires-header {
        text-align: center;
        margin-bottom: 22px;
    }

    .formulaires-header h1 {
        font-size: 28px;
        font-weight: 700;
        color: #003b5c;
        margin-bottom: 8px;
    }

    .formulaires-header p {
        font-size: 13px;
        color: #666;
        margin-bottom: 0;
    }

    .formulaires-container {
        max-width: 1100px;
        margin: 0 auto;
    }

    .formulaires-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 18px;
    }

    .formulaire-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.25s ease;
        overflow: hidden;
        text-decoration: none;
        display: flex;
        flex-direction: column;
        height: 100%;
        cursor: pointer;
    }

    .formulaire-card:hover {
        box-shadow: 0 6px 16px rgba(0, 59, 92, 0.15);
        transform: translateY(-4px);
    }

    .formulaire-card.is-selected {
        border: 2px solid #e8a33d;
        box-shadow: 0 0 0 3px rgba(232, 163, 61, 0.2);
    }

    .formulaire-card-header {
        background: linear-gradient(135deg, #003b5c 0%, #005a8b 100%);
        padding: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 78px;
    }

    .formulaire-card-icon {
        font-size: 32px;
        line-height: 1;
    }

    .formulaire-card-body {
        padding: 12px 14px 14px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .formulaire-card-title {
        font-size: 12.5px;
        font-weight: 600;
        color: #003b5c;
        margin: 0;
        line-height: 1.35;
        flex: 1;
    }

    .category-section {
        margin-bottom: 14px;
    }

    .category-title {
        font-size: 13px;
        font-weight: 700;
        color: #003b5c;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e8a33d;
    }

    .back-button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 20px;
        color: #003b5c;
        font-weight: 600;
        text-decoration: none;
        font-size: 13px;
        transition: all 0.2s ease;
    }

    .back-button:hover {
        color: #005a8b;
        gap: 12px;
    }

    @media (max-width: 768px) {
        .formulaires-header h1 {
            font-size: 22px;
        }

        .formulaires-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
        }

        .formulaire-card-header {
            min-height: 70px;
        }

        .formulaire-card-icon {
            font-size: 26px;
        }

        .formulaire-card-body {
            padding: 10px 12px 12px;
        }
    }

    @media (max-width: 480px) {
        .formulaires-page {
            padding: 12px;
        }

        .formulaires-header {
            margin-bottom: 24px;
        }

        .formulaires-header h1 {
            font-size: 20px;
        }

        .formulaires-grid {
            grid-template-columns: 1fr;
        }

        .category-title {
            font-size: 16px;
        }
    }
</style>

<?php include('includes/preloader.php')?>

<?php include('includes/navbar.php')?>

<?php include('includes/right_sidebar.php')?>

<?php include('includes/left_sidebar.php')?>

<div class="mobile-menu-overlay"></div>

<div class="main-container">
    <div class="pd-ltr-20">
        <div class="formulaires-page">
            <a href="index" class="back-button">← Retour au Dashboard</a>

            <div class="formulaires-header">
                <h1>Formulaires & Documents</h1>
                <p>Sélectionnez un formulaire pour l'ouvrir ou l'imprimer</p>
            </div>

            <div class="flexcube-search-panel" style="max-width:1100px;margin:0 auto 22px;background:#f5f9ff;border:1px solid #cfe0ff;padding:16px;border-radius:10px;">
                <div style="display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;">
                    <div style="flex:1;min-width:220px;">
                        <label for="flexcubeAccountInput" style="display:block;font-weight:700;margin-bottom:6px; color:#003b5c;">Recherche client FlexCube</label>
                        <input id="flexcubeAccountInput" type="text" placeholder="Saisir le numéro de compte" style="width:100%;padding:10px 12px;border:1px solid #b9c8db;border-radius:6px;" autocomplete="off">
                    </div>
                    <button id="flexcubeSearchBtn" type="button" style="padding:10px 16px;border:none;border-radius:6px;background:#004b87;color:#fff;font-weight:700;cursor:pointer;">Rechercher</button>
                    <button id="flexcubeClearBtn" type="button" style="padding:10px 16px;border:none;border-radius:6px;background:#b42318;color:#fff;font-weight:700;cursor:pointer;">Effacer</button>
                </div>
                <div id="flexcubeSearchMessage" style="margin-top:10px;font-size:12px;color:#0f766e;">Aucune recherche effectuée.</div>
            </div>

            <div class="formulaires-container">

                <!-- Catégorie: Ouverture de Compte -->
                <div class="category-section">
                    <h2 class="category-title">Ouverture de Compte</h2>
                    <div class="formulaires-grid">
                        <a href="formulaire_ouverture_compte_tuteur.html" class="formulaire-card">
                            <div class="formulaire-card-header">
                                <div class="formulaire-card-icon">👤</div>
                            </div>
                            <div class="formulaire-card-body">
                                <h3 class="formulaire-card-title">Tuteur (Compte Mineur)</h3>
                            </div>
                        </a>

                        <a href="formulaire_produits" class="formulaire-card">
                            <div class="formulaire-card-header">
                                <div class="formulaire-card-icon">📋</div>
                            </div>
                            <div class="formulaire-card-body">
                                <h3 class="formulaire-card-title">Formulaire Produits</h3>
                            </div>
                        </a>

                        <a href="#" class="formulaire-card" data-flexcube-form="formulaire_omni.html">
                            <div class="formulaire-card-header">
                                <div class="formulaire-card-icon">🏦</div>
                            </div>
                            <div class="formulaire-card-body">
                                <h3 class="formulaire-card-title">Formulaire OMNI ***</h3>
                            </div>
                        </a>

                        <a href="#" class="formulaire-card" data-flexcube-form="formulaire_produits_digitaux.html">
                            <div class="formulaire-card-header">
                                <div class="formulaire-card-icon">📅</div>
                            </div>
                            <div class="formulaire-card-body">
                                <h3 class="formulaire-card-title">Formulaire Produits Digitaux</h3>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Catégorie: Services Bancaires -->
                <div class="category-section">
                    <h2 class="category-title">Services Bancaires</h2>
                    <div class="formulaires-grid">
                        <a href="rib" class="formulaire-card">
                            <div class="formulaire-card-header">
                                <div class="formulaire-card-icon">🧾</div>
                            </div>
                            <div class="formulaire-card-body">
                                <h3 class="formulaire-card-title">Relevé Identité Bancaire (RIB)</h3>
                            </div>
                        </a>

                        <a href="formulaire_operations_diverses_carte.html" class="formulaire-card">
                            <div class="formulaire-card-header">
                                <div class="formulaire-card-icon">💳</div>
                            </div>
                            <div class="formulaire-card-body">
                                <h3 class="formulaire-card-title">Opérations Diverses Carte</h3>
                            </div>
                        </a>

                        <!-- <a href="formulaire_carte" class="formulaire-card">
                            <div class="formulaire-card-header">
                                <div class="formulaire-card-icon">💳</div>
                            </div>
                            <div class="formulaire-card-body">
                                <h3 class="formulaire-card-title">Formulaire Carte</h3>
                            </div>
                        </a> -->

                        <a href="#" class="formulaire-card" data-flexcube-form="fiche_souscription_packs.html">
                            <div class="formulaire-card-header">
                                <div class="formulaire-card-icon">📊</div>
                            </div>
                            <div class="formulaire-card-body">
                                <h3 class="formulaire-card-title">Fiche Souscription Packs</h3>
                            </div>
                        </a>

                        <a href="formulaire_chequier.html" class="formulaire-card">
                            <div class="formulaire-card-header">
                                <div class="formulaire-card-icon">📝</div>
                            </div>
                            <div class="formulaire-card-body">
                                <h3 class="formulaire-card-title">Formulaire Chéquier (Vide)</h3>
                            </div>
                        </a>

                        <a href="#" class="formulaire-card" data-flexcube-form="formulaire_ecobank.html">
                            <div class="formulaire-card-header">
                                <div class="formulaire-card-icon">🏦</div>
                            </div>
                            <div class="formulaire-card-body">
                                <h3 class="formulaire-card-title">Formulaire Banque par Internet</h3>
                            </div>
                        </a>

                        <a href="demande_releve.php" class="formulaire-card">
                            <div class="formulaire-card-header">
                                <div class="formulaire-card-icon">📒</div>
                            </div>
                            <div class="formulaire-card-body">
                                <h3 class="formulaire-card-title">Demande Relevé</h3>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Catégorie: Gestion Compte -->
                <div class="category-section">
                    <h2 class="category-title">Gestion de Compte</h2>
                    <div class="formulaires-grid">
                        <a href="#" data-flexcube-form="formulaire_mise_a_jour_infos_clients.html" class="formulaire-card">
                            <div class="formulaire-card-header">
                                <div class="formulaire-card-icon">🔁</div>
                            </div>
                            <div class="formulaire-card-body">
                                <h3 class="formulaire-card-title">New Formulaire Infos Clients</h3>
                            </div>
                        </a>

                        <a href="#" data-flexcube-form="update_form.html" class="formulaire-card">
                            <div class="formulaire-card-header">
                                <div class="formulaire-card-icon">🔄</div>
                            </div>
                            <div class="formulaire-card-body">
                                <h3 class="formulaire-card-title">Actualisation infos Clients</h3>
                            </div>
                        </a>

                        <a href="procuration.html" class="formulaire-card">
                            <div class="formulaire-card-header">
                                <div class="formulaire-card-icon">👥</div>
                            </div>
                            <div class="formulaire-card-body">
                                <h3 class="formulaire-card-title">Procuration</h3>
                            </div>
                        </a>

                        <a href="frais_de_procuration.html" class="formulaire-card">
                            <div class="formulaire-card-header">
                                <div class="formulaire-card-icon">👥</div>
                            </div>
                            <div class="formulaire-card-body">
                                <h3 class="formulaire-card-title">Frais de Procuration</h3>
                            </div>
                        </a>

                        <a href="#" class="formulaire-card" data-flexcube-form="formulaire_reactivation_comptes_dormants.html">
                            <div class="formulaire-card-header">
                                <div class="formulaire-card-icon">⏳</div>
                            </div>
                            <div class="formulaire-card-body">
                                <h3 class="formulaire-card-title">Formulaire Reactivation Comptes Dormants</h3>
                            </div>
                        </a>

                        <a href="#" class="formulaire-card" data-flexcube-form="formulaire_comptes_airtel.html">
                            <div class="formulaire-card-header">
                                <div class="formulaire-card-icon">📱</div>
                            </div>
                            <div class="formulaire-card-body">
                                <h3 class="formulaire-card-title">Liaison Comptes Airtel</h3>
                            </div>
                        </a>

                        <a href="#" class="formulaire-card" data-flexcube-form="evolution_signature.html">
                            <div class="formulaire-card-header">
                                <div class="formulaire-card-icon">✏️</div>
                            </div>
                            <div class="formulaire-card-body">
                                <h3 class="formulaire-card-title">Evolution de la Signature</h3>
                            </div>
                        </a>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php')?>

<script src="../vendors/scripts/core.js"></script>
<script src="../vendors/scripts/script.min.js"></script>
<script src="../vendors/scripts/process.js"></script>
<script src="../vendors/scripts/layout-settings.js"></script>
<script src="../vendors/js/flexcube_client_search.js"></script>
</body>
</html>
