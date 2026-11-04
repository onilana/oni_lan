document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-admission-countdown]').forEach(function (countdown) {
        var target = Number(countdown.getAttribute('data-admission-countdown')) * 1000;
        if (!Number.isFinite(target) || target <= 0) {
            return;
        }

        function updateCountdown() {
            var remaining = Math.max(0, Math.floor((target - Date.now()) / 1000));
            if (remaining === 0) {
                countdown.textContent = 'now';
                window.location.reload();
                return;
            }

            var days = Math.floor(remaining / 86400);
            var hours = Math.floor((remaining % 86400) / 3600);
            var minutes = Math.floor((remaining % 3600) / 60);
            var seconds = remaining % 60;
            countdown.textContent = days + 'd ' + hours + 'h ' + minutes + 'm ' + seconds + 's';
        }

        updateCountdown();
        window.setInterval(updateCountdown, 1000);
    });

    var downloadForm = document.querySelector('.admission-download-form');
    if (downloadForm) {
        var applicationId = downloadForm.querySelector('[name="application_id"]').value;
        var storageKey = 'oniLanaAdmissionToken:' + applicationId;
        var token = new URLSearchParams(window.location.hash.slice(1)).get('admission_token') || sessionStorage.getItem(storageKey) || '';
        var tokenField = downloadForm.querySelector('[name="admission_token"]');
        var downloadButton = downloadForm.querySelector('button[type="submit"]');
        if (/^[a-f0-9]{64}$/.test(token)) {
            tokenField.value = token;
            downloadButton.disabled = false;
            sessionStorage.setItem(storageKey, token);
            window.history.replaceState(null, '', window.location.pathname + window.location.search);
        }
    }

    document.querySelectorAll('.admission-form input[inputmode="numeric"]').forEach(function (input) {
        input.addEventListener('input', function () {
            input.value = input.value.replace(/\D/g, '').slice(0, input.maxLength);
        });
    });

    var emailInput = document.querySelector('.admission-form input[type="email"]');
    if (emailInput) {
        emailInput.addEventListener('input', function () {
            emailInput.setCustomValidity(emailInput.validity.typeMismatch ? 'Enter a valid email address, such as name@example.com.' : '');
        });
    }

    var totalMarks = document.getElementById('admission-total-marks');
    var marksObtained = document.getElementById('admission-marks-obtained');
    var percentage = document.getElementById('admission-percentage');
    var cgpa = document.getElementById('admission-cgpa');
    var stream = document.getElementById('admission-stream');
    var combination = document.getElementById('admission-subject-combination');
    var otherBoard = document.getElementById('admission-other-board');
    var migrationField = document.getElementById('admission-migration-field');
    var category = document.getElementById('admission-category');
    var categoryCertificateField = document.getElementById('admission-category-certificate-field');
    if (!totalMarks || !marksObtained || !percentage || !cgpa || !stream || !combination || !otherBoard || !migrationField || !category || !categoryCertificateField) {
        return;
    }

    var combinations = {
        arts: [
            ['arts-history-physical', 'English, History, Political Science, Sociology, Home Science, Physical Education'],
            ['arts-economics-physical', 'English, History, Political Science, Sociology, Economics, Physical Education']
        ],
        science: [
            ['science-biology-home', 'English, Physics, Chemistry, Biology, Home Science, Physical Education'],
            ['science-biology-maths', 'English, Physics, Chemistry, Biology, Mathematics, Physical Education'],
            ['science-maths-home', 'English, Physics, Chemistry, Mathematics, Home Science, Physical Education']
        ],
        commerce: [
            ['commerce-maths', 'English, Business Studies, Accountancy, Mathematics, Economics, Computer Science'],
            ['commerce-entrepreneurship', 'English, Business Studies, Accountancy, Entrepreneurship, Economics, Computer Science']
        ]
    };

    function updatePercentage() {
        var total = Number(totalMarks.value);
        var obtained = Number(marksObtained.value);

        marksObtained.max = total > 0 ? total : '';
        if (total > 0 && marksObtained.value !== '' && obtained >= 0 && obtained <= total) {
            percentage.value = ((obtained / total) * 100).toFixed(2) + '%';
        } else {
            percentage.value = '';
        }
        updateAcademicRequirements();
    }

    function updateAcademicRequirements() {
        var hasCgpa = cgpa.value !== '';
        var hasMarks = totalMarks.value !== '' || marksObtained.value !== '';
        cgpa.required = percentage.value === '';
        totalMarks.required = !hasCgpa || hasMarks;
        marksObtained.required = !hasCgpa || hasMarks;
    }

    function updateCombinations() {
        var options = combinations[stream.value] || [];
        combination.replaceChildren();

        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = options.length ? 'Select a subject combination' : 'Select a stream first';
        combination.appendChild(placeholder);

        options.forEach(function (item) {
            var option = document.createElement('option');
            option.value = item[0];
            option.textContent = item[1];
            combination.appendChild(option);
        });
        combination.disabled = options.length === 0;
    }

    function setConditionalFile(field, required) {
        var input = field.querySelector('input[type="file"]');
        field.hidden = !required;
        input.disabled = !required;
        input.required = required;
        if (!required) {
            input.value = '';
        }
    }

    totalMarks.addEventListener('input', updatePercentage);
    marksObtained.addEventListener('input', updatePercentage);
    cgpa.addEventListener('input', updateAcademicRequirements);
    document.querySelectorAll('.admission-form input[type="file"]').forEach(function (input) {
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            input.setCustomValidity(file && file.size > 200 * 1024 ? 'Each file must be 200 KB or smaller.' : '');
        });
    });
    stream.addEventListener('change', updateCombinations);
    otherBoard.addEventListener('change', function () {
        setConditionalFile(migrationField, otherBoard.value === 'yes');
    });
    category.addEventListener('change', function () {
        setConditionalFile(categoryCertificateField, category.value !== '' && category.value !== 'General');
    });

    updatePercentage();
    updateCombinations();
});
