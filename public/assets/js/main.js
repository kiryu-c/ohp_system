// public/assets/js/main.js

document.addEventListener('DOMContentLoaded', function() {
    // アラートの自動非表示
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        if (!alert.querySelector('.btn-close')) {
            setTimeout(function() {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 300);
            }, 5000);
        }
    });

    // フォームバリデーション
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // 確認ダイアログ
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(event) {
            const message = button.dataset.confirm || '本当に削除しますか？';
            if (!confirm(message)) {
                event.preventDefault();
            }
        });
    });

    // テーブルの行クリック
    const clickableRows = document.querySelectorAll('[data-href]');
    clickableRows.forEach(function(row) {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function() {
            window.location = row.dataset.href;
        });
    });

    // 数値フォーマット
    const numberInputs = document.querySelectorAll('input[type="number"]');
    numberInputs.forEach(function(input) {
        input.addEventListener('blur', function() {
            if (input.value && !isNaN(input.value)) {
                input.value = parseFloat(input.value).toFixed(2);
            }
        });
    });

    // 時間計算機能
    function calculateHours(startTime, endTime) {
        if (!startTime || !endTime) return 0;
        
        const start = new Date(`1970-01-01T${startTime}:00`);
        const end = new Date(`1970-01-01T${endTime}:00`);
        
        if (end <= start) return 0;
        
        const diff = end - start;
        return diff / (1000 * 60 * 60);
    }

    // 役務時間の自動計算
    const startTimeInputs = document.querySelectorAll('input[name="start_time"]');
    const endTimeInputs = document.querySelectorAll('input[name="end_time"]');
    
    function updateCalculatedHours() {
        const startTime = document.querySelector('input[name="start_time"]')?.value;
        const endTime = document.querySelector('input[name="end_time"]')?.value;
        const calculatedHoursElement = document.getElementById('calculated_hours');
        
        if (startTime && endTime && calculatedHoursElement) {
            const hours = calculateHours(startTime, endTime);
            calculatedHoursElement.textContent = hours.toFixed(2);
        }
    }

    startTimeInputs.forEach(function(input) {
        input.addEventListener('change', updateCalculatedHours);
    });
    
    endTimeInputs.forEach(function(input) {
        input.addEventListener('change', updateCalculatedHours);
    });

    // 月次フィルター
    const monthSelect = document.getElementById('month_filter');
    const yearSelect = document.getElementById('year_filter');
    
    if (monthSelect && yearSelect) {
        function updateFilter() {
            const month = monthSelect.value;
            const year = yearSelect.value;
            const url = new URL(window.location);
            
            if (month) url.searchParams.set('month', month);
            else url.searchParams.delete('month');
            
            if (year) url.searchParams.set('year', year);
            else url.searchParams.delete('year');
            
            window.location = url.toString();
        }
        
        monthSelect.addEventListener('change', updateFilter);
        yearSelect.addEventListener('change', updateFilter);
    }

    // トグルボタン
    const toggleButtons = document.querySelectorAll('[data-toggle]');
    toggleButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const target = document.querySelector(button.dataset.toggle);
            if (target) {
                target.style.display = target.style.display === 'none' ? 'block' : 'none';
            }
        });
    });

    // 検索機能
    const searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = searchInput.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(function(row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // ソート機能
    const sortButtons = document.querySelectorAll('[data-sort]');
    sortButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const table = button.closest('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const column = parseInt(button.dataset.sort);
            const isAsc = button.classList.contains('asc');
            
            rows.sort(function(a, b) {
                const aText = a.children[column].textContent.trim();
                const bText = b.children[column].textContent.trim();
                
                if (isAsc) {
                    return aText.localeCompare(bText);
                } else {
                    return bText.localeCompare(aText);
                }
            });
            
            // ソート方向を切り替え
            sortButtons.forEach(function(btn) {
                btn.classList.remove('asc', 'desc');
            });
            
            button.classList.add(isAsc ? 'desc' : 'asc');
            
            // 行を再配置
            rows.forEach(function(row) {
                tbody.appendChild(row);
            });
        });
    });

    // タブ機能
    const tabButtons = document.querySelectorAll('[data-tab]');
    tabButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const tabId = button.dataset.tab;
            
            // すべてのタブボタンの active クラスを削除
            tabButtons.forEach(function(btn) {
                btn.classList.remove('active');
            });
            
            // すべてのタブコンテンツを非表示
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(function(content) {
                content.style.display = 'none';
            });
            
            // クリックされたタブをアクティブに
            button.classList.add('active');
            const targetContent = document.getElementById(tabId);
            if (targetContent) {
                targetContent.style.display = 'block';
            }
        });
    });

    // ページネーション
    const paginationLinks = document.querySelectorAll('.pagination a');
    paginationLinks.forEach(function(link) {
        link.addEventListener('click', function(event) {
            if (link.parentElement.classList.contains('disabled')) {
                event.preventDefault();
            }
        });
    });

    // 印刷機能
    const printButtons = document.querySelectorAll('[data-print]');
    printButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            window.print();
        });
    });

    // エクスポート機能（CSV）
    const exportButtons = document.querySelectorAll('[data-export="csv"]');
    exportButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const table = button.closest('.card').querySelector('table');
            if (table) {
                exportTableToCSV(table, 'export.csv');
            }
        });
    });

    function exportTableToCSV(table, filename) {
        const csv = [];
        const rows = table.querySelectorAll('tr');
        
        rows.forEach(function(row) {
            const cols = row.querySelectorAll('td, th');
            const rowData = [];
            
            cols.forEach(function(col) {
                rowData.push('"' + col.textContent.trim().replace(/"/g, '""') + '"');
            });
            
            csv.push(rowData.join(','));
        });

        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        
        if (link.download !== undefined) {
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }

    // リアルタイムバリデーション
    const emailInputs = document.querySelectorAll('input[type="email"]');
    emailInputs.forEach(function(input) {
        input.addEventListener('blur', function() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (input.value && !emailRegex.test(input.value)) {
                input.setCustomValidity('有効なメールアドレスを入力してください');
            } else {
                input.setCustomValidity('');
            }
        });
    });

    // パスワード強度チェック
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(function(input) {
        input.addEventListener('input', function() {
            const password = input.value;
            const strengthIndicator = document.getElementById('password-strength');
            
            if (strengthIndicator && password.length > 0) {
                let strength = 0;
                
                if (password.length >= 8) strength++;
                if (/[a-z]/.test(password)) strength++;
                if (/[A-Z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                if (/[^A-Za-z0-9]/.test(password)) strength++;
                
                const strengthLabels = ['非常に弱い', '弱い', '普通', '強い', '非常に強い'];
                const strengthColors = ['danger', 'warning', 'info', 'success', 'success'];
                
                strengthIndicator.textContent = strengthLabels[strength] || '';
                strengthIndicator.className = 'form-text text-' + (strengthColors[strength] || 'muted');
            }
        });
    });
});