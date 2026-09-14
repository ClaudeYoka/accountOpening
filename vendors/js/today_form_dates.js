(function () {
  function isIdentityDateField(field) {
    if (field.name === 'visaDe' || field.name === 'visaA') return true;
    if (field.name === 'dateOuverture') return true;
    var context = field.closest('.field, .date-row, .date-line, .date-container');
    var marker = (field.name || '') + ' ' + (field.id || '') + ' ' + (context ? context.textContent : '');
    return /naissance|birth|d[ée]livrance|[ée]mission|expiration|expiry|passeport|passport|pi[èe]ce|document|carte.*date|date.*carte/i.test(marker);
  }

  function setFieldValue(field, value) {
    if (!field || isIdentityDateField(field)) return;
    field.value = value;
    field.dispatchEvent(new Event('input', { bubbles: true }));
    field.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function fillTodayDates() {
    var now = new Date();
    var day = String(now.getDate()).padStart(2, '0');
    var month = String(now.getMonth() + 1).padStart(2, '0');
    var year = String(now.getFullYear());
    var isoDate = year + '-' + month + '-' + day;

    document.querySelectorAll('input[type="date"]').forEach(function (field) {
      setFieldValue(field, isoDate);
    });

    var namedDates = { jour: day, mois: month, annee: year };
    Object.keys(namedDates).forEach(function (name) {
      document.querySelectorAll('[name="' + name + '"]').forEach(function (field) {
        setFieldValue(field, namedDates[name]);
      });
    });

    document.querySelectorAll('[data-group^="date"]').forEach(function (group) {
      if (/date(id|expid)/i.test(group.dataset.group || '')) return;
      var value = day + month + year;
      group.querySelectorAll('input').forEach(function (field, index) {
        if (index < value.length) setFieldValue(field, value[index]);
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fillTodayDates);
  } else {
    fillTodayDates();
  }
})();
