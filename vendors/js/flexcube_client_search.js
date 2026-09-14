(function () {
  const STORAGE_KEY = 'ecobank_flexcube_client_data';
  const FORM_OPTIONS = [
    { value: 'fiche_souscription_packs.html', label: 'Fiche souscription packs' },
    { value: 'formulaire_comptes_airtel.html', label: 'Formulaire comptes Airtel' },
    { value: 'formulaire_produits_digitaux.html', label: 'Formulaire produits digitaux' },
    { value: 'formulaire_ecobank.html', label: 'Formulaire Banque par Internet' },
    { value: 'frais_de_procuration.html', label: 'Frais de procuration' },
    { value: 'procuration.html', label: 'Procuration' },
    { value: 'formulaire_reactivation_comptes_dormants.html', label: 'Réactivation comptes dormants' },
    { value: 'formulaire_mise_a_jour_infos_clients.html', label: 'Mise à jour des infos clients' },
    { value: 'update_form.html', label: 'Actualisation des infos clients' }
  ];

  function setSelectedCardState(selectedValue) {
    document.querySelectorAll('.formulaire-card[data-flexcube-form]').forEach(function (card) {
      const isSelected = card.dataset.flexcubeForm === selectedValue;
      card.classList.toggle('is-selected', isSelected);
      card.setAttribute('aria-selected', isSelected ? 'true' : 'false');
    });
  }

  function restoreSelectedCard() {
    const selected = localStorage.getItem('ecobank_flexcube_selected_form');
    if (selected) {
      setSelectedCardState(selected);
    }
  }

  function safeText(value) {
    if (value === null || value === undefined) return '';
    return String(value).trim();
  }

  function isDigitalProductsForm() {
    return window.location.pathname.split('/').pop().toLowerCase() === 'formulaire_produits_digitaux.html';
  }

  function isCardOperationsForm() {
    return window.location.pathname.split('/').pop().toLowerCase() === 'formulaire_operations_diverses_carte.html';
  }

  function isProcurationForm() {
    const page = window.location.pathname.split('/').pop().toLowerCase();
    return page === 'procuration.html' || page === 'frais_de_procuration.html';
  }

  function isStaffAutoFillForm() {
    const page = window.location.pathname.split('/').pop().toLowerCase();
    return page === 'formulaire_produits_digitaux.html' || page === 'fiche_souscription_packs.html' || page === 'formulaire_operations_diverses_carte.html';
  }

  function fillDigitalProductsStaffFields() {
    if (!isStaffAutoFillForm()) return;

    const now = new Date();
    const today = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
    const clientDate = getFieldByName('dateClient');
    const agentDate = getFieldByName('dateAgent');
    if (clientDate) setInputValue(clientDate, today);
    if (agentDate) setInputValue(agentDate, today);

    fetch('current_user.php', { headers: { Accept: 'application/json' } })
      .then(function (response) { return response.ok ? response.json() : null; })
      .then(function (user) {
        if (user && user.full_name) applyMappedValue(['nomAgent'], user.full_name);
      })
      .catch(function () {});
  }

  function isIdentityDateField(field) {
    if (field.name === 'visaDe' || field.name === 'visaA') return true;
    if (field.name === 'dateOuverture') return true;
    const context = field.closest('.field, .date-row, .date-line, .date-container');
    const marker = ((field.name || '') + ' ' + (field.id || '') + ' ' + (context ? context.textContent : '')).toLowerCase();
    return /naissance|birth|d[ée]livrance|[ée]mission|expiration|expiry|passeport|passport|pi[èe]ce|document|carte.*date|date.*carte/.test(marker);
  }

  function fillTodayDates() {
    const now = new Date();
    const today = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const year = String(now.getFullYear());

    document.querySelectorAll('input[type="date"]').forEach(function (field) {
      if (!isIdentityDateField(field)) setInputValue(field, today);
    });

    const dateGroups = {
      jour: day,
      mois: month,
      annee: year
    };
    Object.keys(dateGroups).forEach(function (name) {
      const field = getFieldByName(name);
      if (field && !isIdentityDateField(field)) setInputValue(field, dateGroups[name]);
    });

    document.querySelectorAll('[data-group^="date"]').forEach(function (group) {
      if (/date(id|expid)/i.test(group.dataset.group || '')) return;
      const boxes = group.querySelectorAll('input');
      const value = day + month + year;
      boxes.forEach(function (box, index) {
        if (index < value.length && !isIdentityDateField(box)) setInputValue(box, value[index]);
      });
    });
  }

  function escapeCss(value) {
    return value.replace(/([:#.\[\],=])/g, '\\$1');
  }

  function getFieldByName(name) {
    if (!name) return null;
    return document.querySelector('[name="' + escapeCss(name) + '"]') || document.getElementById(name) || null;
  }

  function setInputValue(field, value) {
    if (!field || value === null || value === undefined || value === '') return false;
    const text = String(value).trim();
    if (field.tagName === 'SELECT') {
      const option = Array.from(field.options).find((opt) => {
        return opt.value.toLowerCase() === text.toLowerCase() || opt.textContent.trim().toLowerCase() === text.toLowerCase();
      });
      if (option) {
        field.value = option.value;
      } else {
        field.value = text;
      }
    } else {
      field.value = text;
    }
    field.dispatchEvent(new Event('input', { bubbles: true }));
    field.dispatchEvent(new Event('change', { bubbles: true }));
    return true;
  }

  function fillTextBlock(selector, value) {
    const target = document.querySelector(selector);
    if (!target || !value) return false;
    target.value = String(value).trim();
    target.dispatchEvent(new Event('input', { bubbles: true }));
    target.dispatchEvent(new Event('change', { bubbles: true }));
    return true;
  }

  function fillDigitGroup(selector, value) {
    if (!selector || !value) return false;
    const boxes = document.querySelectorAll(selector + ' input[type="text"], ' + selector + ' input:not([type])');
    if (!boxes || boxes.length === 0) return false;
    const digits = safeText(value).replace(/\D/g, '').slice(0, boxes.length);
    boxes.forEach(function (box, index) {
      box.value = digits[index] || '';
      box.dispatchEvent(new Event('input', { bubbles: true }));
    });
    return true;
  }

  function fillCharacterGroups(selector, value) {
    if (!selector || value === null || value === undefined) return false;
    const boxes = document.querySelectorAll(selector + ' input[type="text"]');
    if (!boxes || boxes.length === 0) return false;
    const characters = Array.from(safeText(value)).slice(0, boxes.length);
    boxes.forEach(function (box, index) {
      box.value = characters[index] || '';
      box.dispatchEvent(new Event('input', { bubbles: true }));
    });
    return true;
  }

  function getFirstLastNames(data) {
    const fullName = safeText(data.customer_name || data.account_name || data.account_title || data.nom || data.name);
    if (fullName) {
      const parts = fullName.split(/\s+/);
      const first = parts.shift() || '';
      const last = parts.join(' ');
      return { firstName: first, lastName: last || first };
    }

    const firstName = safeText(data.first_name || data.prenom || data.given_name);
    const lastName = safeText(data.last_name || data.nom || data.family_name || data.surname);
    return { firstName, lastName: lastName || firstName };
  }

  function applyMappedValue(mapNames, value) {
    if (!value) return false;

    for (const name of mapNames) {
      const field = getFieldByName(name);
      if (field && setInputValue(field, value)) {
        return true;
      }
    }

    if (mapNames.includes('nom')) {
      const fallback = document.querySelector('input[name="nom"]');
      if (fallback && setInputValue(fallback, value)) return true;
    }

    return false;
  }

  function fillEcobankAccountTable(data, raw) {
    const source = Object.assign(
      {},
      raw || {},
      raw && raw.data ? raw.data : {},
      data || {},
      data && data.data ? data.data : {}
    );
    const valueFrom = function (keys) {
      for (const key of keys) {
        const sourceKey = Object.keys(source).find(function (candidate) {
          return candidate.toLowerCase() === key.toLowerCase();
        });
        const value = sourceKey ? source[sourceKey] : '';
        if (value !== null && value !== undefined && String(value).trim() !== '') {
          return value;
        }
      }
      return '';
    };

    const values = {
      compte1_numero: valueFrom(['account_number', 'cust_ac_no', 'accountNumber']),
      compte1_type: valueFrom(['account_type', 'description', 'type_compte', 'account_class_description', 'account_class']),
      compte1_devise: valueFrom(['currency', 'devise', 'ccy', 'currency_code']),
      compte1_agence: valueFrom(['branch_code', 'agence', 'branch', 'agency_code']),
      compte1_filiale: 'ECG'
    };

    Object.keys(values).forEach(function (fieldName) {
      const field = getFieldByName(fieldName);
      if (field) setInputValue(field, values[fieldName]);
    });
  }

  function fillUpdateForms(data, raw) {
    const source = Object.assign({}, raw || {}, data || {});
    const accountNumber = source.account_number || source.accountNumber || source.cust_ac_no || '';
    const accountName = source.account_name || source.account_title || source.customer_name || '';
    const mobile = source.telephone || source.phone_number || source.mobile || source.phone || '';
    const email = source.email || source.email_address || source.e_mail || source.mail || '';
    const address = source.customer_address || source.account_address || source.address || '';

    fillDigitGroup('[data-group="account-number"]', accountNumber);

    if (window.location.pathname.split('/').pop().toLowerCase() === 'formulaire_mise_a_jour_infos_clients.html') {
      applyMappedValue(['pays_affilie'], 'ECG');
      applyMappedValue(['code_affiliation'], 'ECG');
      return;
    }

    if (window.location.pathname.split('/').pop().toLowerCase() === 'update_form.html') {
      return;
    }

    fillDigitGroup('[data-group="mobile"]', mobile);
    fillCharacterGroups('[data-group="email1"], [data-group="email2"]', email);
    fillCharacterGroups('[data-group="name1"], [data-group="name2"]', accountName);

    applyMappedValue(['numero_compte_client'], accountNumber);
    applyMappedValue(['nom_compte_client'], accountName);
    applyMappedValue(['nom_succursale'], source.branch_name || source.branch || '');
    applyMappedValue(['code_succursale', 'branch_code'], source.branch_code || '');
    applyMappedValue(['pays_affilie'], 'ECG');
    applyMappedValue(['code_affiliation'], 'ECG');
    applyMappedValue(['nom_rue', 'adresse1', 'adresse'], address);
    applyMappedValue(['pays'], source.country || source.pays || 'Congo');
  }

  function applyClientData(payload) {
    if (!payload) return;

    const data = payload.data || payload;
    const raw = payload.raw || {};
    const merged = Object.assign({}, raw, data);
    const names = getFirstLastNames(merged);

    fillEcobankAccountTable(data, raw);
    fillUpdateForms(Object.assign({}, raw, data), raw);

    applyMappedValue(['nom', 'last_name', 'lastName', 'family-name'], names.lastName || merged.last_name || merged.nom || merged.customer_name || merged.account_name);
    applyMappedValue(['prenom', 'first_name', 'firstName', 'given-name'], names.firstName || merged.first_name || merged.prenom);

    applyMappedValue(['numeroCompte', 'account_number', 'accountNumber', 'account-num', 'numero_compte', 'compteDebit'], merged.account_number || merged.accountNumber || merged.bank_account_number);
    applyMappedValue(['codeClient', 'customer_id', 'customerId'], merged.customer_id || merged.customerId || merged.customer_no || merged.cust_no || merged.ID);
    applyMappedValue(['intituleCompte', 'customer_name', 'account_name', 'account_title', 'intitule_compte'], merged.customer_name || merged.account_name || merged.account_title || merged.name);
    applyMappedValue(['telephone', 'phone_number', 'phone', 'tel', 'telephoneCompte', 'telephone_mobile', 'mobile'], merged.telephone || merged.phone_number || merged.mobile || merged.phone || merged.tel);
    applyMappedValue(['email', 'adresse_email', 'mail', 'email_address'], merged.email || merged.email_address || merged.mail || merged.adresse_email || merged.e_mail);
    applyMappedValue(['nationalite', 'nationality', 'nationalite_client'], merged.nationality || merged.nationalite);
    applyMappedValue(['adresse', 'adresse1', 'customer_address', 'address', 'residential_address', 'adresse_client'], merged.customer_address || merged.address || merged.residential_address);
    applyMappedValue(['profession', 'occupation', 'profession_client'], merged.occupation || merged.profession);
    applyMappedValue(['autorisationNom', 'nom_client', 'customer_name'], merged.customer_name || merged.account_name || merged.account_title);
    applyMappedValue(['autorisationCompte', 'account_number', 'numeroCompte'], merged.account_number || merged.accountNumber);
    applyMappedValue(['nomPrenoms', 'nom', 'nom_client', 'customer_name', 'account_name'], merged.customer_name || merged.account_name || merged.account_title || [names.firstName, names.lastName].filter(Boolean).join(' '));

    if (isPacksForm()) {
      applyMappedValue(['nom'], merged.last_name || merged.lastName || '');
      applyMappedValue(['prenom'], [merged.first_name, merged.middle_name].filter(Boolean).join(' '));
      const birthDate = safeText(merged.date_of_birth || merged.date_naissance || merged.birth_date);
      fillPacksBirthDate(birthDate);
      fillDigitalProductsStaffFields();
    }

    if (isProcurationForm()) {
      applyMappedValue(['nom'], merged.customer_name || merged.account_name || merged.account_title || [names.lastName, names.firstName].filter(Boolean).join(' '));
      applyMappedValue(['numeroCompte'], merged.account_number || merged.accountNumber || merged.bank_account_number);
      applyMappedValue(['agence'], mapAgencyName(merged.branch_code || merged.branch || merged.agency_code));
      applyMappedValue(['nomSoussigne'], merged.customer_name || merged.account_name || merged.account_title || [names.lastName, names.firstName].filter(Boolean).join(' '));
      applyMappedValue(['dateOuverture'], formatOpeningDate(merged.opening_date || merged.date_open || merged.date_ouverture));
      applyMappedValue(['dateSignature'], new Date().toLocaleDateString('fr-FR'));
    }

    applyMappedValue(['email', 'adresse_email', 'mail', 'email_address', 'e_mail'], merged.email || merged.email_address || merged.mail || merged.adresse_email || merged.e_mail);

    const accountNumber = safeText(payload.account_number || merged.account_number || merged.accountNumber || merged.bank_account_number || merged.cust_ac_no);
    if (accountNumber) {
      fillDigitGroup('[data-group="compte"]', accountNumber);
      if (!isDigitalProductsForm() && !isCardOperationsForm()) {
        fillDigitGroup('[data-group="carte"]', accountNumber.replace(/\D/g, '').slice(0, 15));
      }
    }

    const contactAddress = safeText(merged.customer_address || merged.address || merged.residential_address);
    if (contactAddress) {
      const addressParts = contactAddress.split(/\s{2,}|\n+/).filter(Boolean);
      if (addressParts[0]) {
        const firstField = getFieldByName('adresse1') || getFieldByName('adresseResidentielle1') || document.querySelector('input[name="adresseResidentielle1"]');
        if (firstField) setInputValue(firstField, addressParts[0]);
      }
      if (addressParts[1]) {
        const secondField = getFieldByName('adresse2') || getFieldByName('adresseResidentielle2') || document.querySelector('input[name="adresseResidentielle2"]');
        if (secondField) setInputValue(secondField, addressParts.slice(1).join(' '));
      }
    }

    if (document.querySelector('input[name="telephoneLier"], input[name="telephone_lier"], input[name="telLier"]')) {
      const linkedPhone = safeText(merged.telephone || merged.phone_number || merged.mobile || merged.phone);
      applyMappedValue(['telephoneLier', 'telephone_lier', 'telLier'], linkedPhone);
    }

    if (document.querySelector('input[name="nomTitulaire"], input[name="nom_titulaire"]')) {
      const titulaire = safeText(merged.customer_name || merged.account_name || merged.account_title || [names.firstName, names.lastName].filter(Boolean).join(' '));
      applyMappedValue(['nomTitulaire', 'nom_titulaire'], titulaire);
      applyMappedValue(['nomBeneficiaire', 'nom_beneficiaire'], titulaire);
    }

    if (document.querySelector('input[name="mobile"], input[name="contactMobile"]')) {
      applyMappedValue(['mobile', 'contactMobile', 'telephone'], merged.telephone || merged.phone_number || merged.mobile || merged.phone);
    }

    if (!isDigitalProductsForm() && document.querySelector('textarea[name="commentaire"]')) {
      const comment = 'Compte FlexCube: ' + (accountNumber || '');
      const textarea = document.querySelector('textarea[name="commentaire"]');
      if (textarea) textarea.value = comment;
    }

  }

  function isPacksForm() {
    return window.location.pathname.split('/').pop().toLowerCase() === 'fiche_souscription_packs.html';
  }

  function fillPacksBirthDate(value) {
    if (!value) return;
    const normalizedValue = String(value).trim();
    const dateParts = normalizedValue.match(/^(\d{4})[-\/](\d{2})[-\/](\d{2})$/) || normalizedValue.match(/^(\d{2})[-\/](\d{2})[-\/](\d{4})$/);
    if (!dateParts) return;
    const dateValue = dateParts[1].length === 4
      ? dateParts[1] + '-' + dateParts[2] + '-' + dateParts[3]
      : dateParts[3] + '-' + dateParts[2] + '-' + dateParts[1];
    const field = getFieldByName('dateNaissance');
    if (field) setInputValue(field, dateValue);
  }

  function formatOpeningDate(value) {
    const normalizedValue = safeText(value);
    if (!normalizedValue) return '';

    const isoMatch = normalizedValue.match(/^(\d{4})[-\/](\d{2})[-\/](\d{2})/);
    if (isoMatch) return isoMatch[3] + '/' + isoMatch[2] + '/' + isoMatch[1];

    const frenchMatch = normalizedValue.match(/^(\d{2})[-\/](\d{2})[-\/](\d{4})/);
    if (frenchMatch) return frenchMatch[1] + '/' + frenchMatch[2] + '/' + frenchMatch[3];

    return normalizedValue;
  }

  function mapAgencyName(value) {
    const agencyNames = {
      T31: 'SIÈGE',
      T32: 'LUMUMBA',
      T33: 'ATLANTIC',
      T34: 'POTO-POTO',
      T38: 'DOLISIE',
      T39: 'OUESSO'
    };
    const agencyCode = safeText(value).toUpperCase();
    return agencyNames[agencyCode] || safeText(value);
  }
  function showSearchMessage(message, isError) {
    const node = document.getElementById('flexcubeSearchMessage');
    if (!node) return;
    node.textContent = message;
    node.style.color = isError ? '#b42318' : '#0f766e';
  }

  function clearStoredClientData() {
    localStorage.removeItem(STORAGE_KEY);
    const input = document.getElementById('flexcubeAccountInput');
    if (input) input.value = '';
    showSearchMessage('Les données FlexCube ont été effacées.', false);
  }

  function fetchAccountData(accountNumber) {
    const url = 'fetch_account_flexcube.php?account=' + encodeURIComponent(accountNumber);
    return fetch(url, { headers: { Accept: 'application/json' } })
      .then(function (response) {
        return response.text().then(function (text) {
          try {
            return { status: response.status, json: JSON.parse(text) };
          } catch (e) {
            throw new Error('Réponse JSON invalide du serveur');
          }
        });
      })
      .then(function (result) {
        if (result.status !== 200 || !result.json || !result.json.success) {
          throw new Error((result.json && result.json.error) || 'Compte introuvable dans Flexcube');
        }
        return result.json;
      });
  }

  function renderFormSelector() {
    const selector = document.getElementById('flexcubeFormSelector');
    if (selector) {
      selector.innerHTML = FORM_OPTIONS.map(function (option) {
        return '<option value="' + option.value + '">' + option.label + '</option>';
      }).join('');
    }

    const currentPage = window.location.pathname.split('/').pop();
    let selectedValue = FORM_OPTIONS[0].value;
    if (currentPage) {
      const currentIndex = FORM_OPTIONS.findIndex(function (item) { return item.value === currentPage; });
      if (currentIndex >= 0) {
        selectedValue = FORM_OPTIONS[currentIndex].value;
      }
    }

    if (document.getElementById('flexcubeFormSelector')) {
      document.getElementById('flexcubeFormSelector').value = selectedValue;
    }
    setSelectedCardState(selectedValue);
  }

  function bindFormCardSelection() {
    document.querySelectorAll('.formulaire-card[data-flexcube-form]').forEach(function (card) {
      card.addEventListener('click', function (event) {
        event.preventDefault();
        const target = card.dataset.flexcubeForm;
        if (!target) return;
        setSelectedCardState(target);
        localStorage.setItem('ecobank_flexcube_selected_form', target);
      });

      card.addEventListener('dblclick', function (event) {
        event.preventDefault();
        const target = card.dataset.flexcubeForm;
        if (!target) return;
        window.location.href = target;
      });
    });
  }

  function attachSearchEvents() {
    const input = document.getElementById('flexcubeAccountInput');
    const btn = document.getElementById('flexcubeSearchBtn');
    const clearBtn = document.getElementById('flexcubeClearBtn');
    const openBtn = document.getElementById('flexcubeOpenBtn');
    const selector = document.getElementById('flexcubeFormSelector');

    if (clearBtn) {
      clearBtn.addEventListener('click', function () {
        clearStoredClientData();
      });
    }

    if (btn && input) {
      btn.addEventListener('click', function () {
        const accountNumber = safeText(input.value);
        if (!accountNumber) {
          showSearchMessage('Veuillez saisir un numéro de compte.', true);
          return;
        }

        showSearchMessage('Recherche du client dans FlexCube...', false);
        fetchAccountData(accountNumber)
          .then(function (json) {
            const result = {
              account_number: accountNumber,
              data: json.data,
              raw: json.raw || {}
            };
            localStorage.setItem(STORAGE_KEY, JSON.stringify(result));
            applyClientData(result);
            showSearchMessage('Client trouvé et données chargées pour le formulaire courant.', false);
          })
          .catch(function (error) {
            console.error(error);
            showSearchMessage(error.message || 'Compte introuvable', true);
          });
      });
    }

    bindFormCardSelection();

    if (!input && document.querySelectorAll('.formulaire-card[data-flexcube-form]').length) {
      document.querySelectorAll('.formulaire-card[data-flexcube-form]').forEach(function (card) {
        card.tabIndex = 0;
        card.addEventListener('keydown', function (event) {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            card.click();
          }
        });
      });
    }

    if (input && input.addEventListener) {
      input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && btn) {
          btn.click();
        }
      });
    }
  }

  function readStoredClient() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch (e) {
      return null;
    }
  }

  function init() {
    renderFormSelector();
    restoreSelectedCard();
    attachSearchEvents();
    fillDigitalProductsStaffFields();
    fillTodayDates();

    const stored = readStoredClient();
    if (stored && document.getElementById('flexcubeAccountInput')) {
      document.getElementById('flexcubeAccountInput').value = stored.account_number || '';
    }

    if (stored) {
      applyClientData(stored);
      const msg = document.getElementById('flexcubeSearchMessage');
      if (msg) {
        msg.textContent = 'Données FlexCube déjà chargées dans la session.';
        msg.style.color = '#0f766e';
      }
    }
  }

  document.addEventListener('DOMContentLoaded', init);
})();
