document.addEventListener('DOMContentLoaded', function() {
    // フラッシュメッセージの自動非表示
    const successAlert = document.getElementById('success-alert');
    const errorAlert = document.getElementById('error-alert');
    
    if (successAlert) {
      setTimeout(function() {
        successAlert.classList.add('fade-out');
        setTimeout(function() {
          successAlert.style.display = 'none';
        }, 500);
      }, 4000);
    }
    
    if (errorAlert) {
      setTimeout(function() {
        errorAlert.classList.add('fade-out');
        setTimeout(function() {
          errorAlert.style.display = 'none';
        }, 500);
      }, 5000);
    }
    
    // モーダル関連の共通処理
    const openModalButtons = document.querySelectorAll('[data-modal]');
    const closeModalButtons = document.querySelectorAll('[data-close-modal]');
    
    openModalButtons.forEach(button => {
      button.addEventListener('click', () => {
        const modalId = button.getAttribute('data-modal');
        const modal = document.getElementById(modalId);
        if (modal) {
          modal.classList.remove('hidden');
        }
      });
    });
    
    closeModalButtons.forEach(button => {
      button.addEventListener('click', () => {
        const modal = button.closest('[id$="-modal"]');
        if (modal) {
          modal.classList.add('hidden');
        }
      });
    });
    
    // 外部クリックでモーダルを閉じる
    document.addEventListener('click', (event) => {
      const modals = document.querySelectorAll('[id$="-modal"]:not(.hidden)');
      modals.forEach(modal => {
        const modalContent = modal.querySelector('.bg-white');
        if (modalContent && !modalContent.contains(event.target) && !event.target.hasAttribute('data-modal')) {
          modal.classList.add('hidden');
        }
      });
    }, true);
    
    // ESCキーでモーダルを閉じる
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        const modals = document.querySelectorAll('[id$="-modal"]:not(.hidden)');
        modals.forEach(modal => {
          modal.classList.add('hidden');
        });
      }
    });
    
    // 金額フォーマットのヘルパー関数
    window.formatCurrency = function(amount) {
      return '¥' + parseFloat(amount).toLocaleString('ja-JP', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      });
    };
    
    // 日付フォーマットのヘルパー関数
    window.formatDate = function(date, format = 'YYYY/MM/DD') {
      if (typeof date === 'string') {
        date = new Date(date);
      }
      
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      const hours = String(date.getHours()).padStart(2, '0');
      const minutes = String(date.getMinutes()).padStart(2, '0');
      const seconds = String(date.getSeconds()).padStart(2, '0');
      
      return format
        .replace('YYYY', year)
        .replace('MM', month)
        .replace('DD', day)
        .replace('HH', hours)
        .replace('mm', minutes)
        .replace('ss', seconds);
    };
    
    // 確認ダイアログ
    window.confirmDialog = function(message, callback) {
      if (confirm(message)) {
        callback();
      }
    };
    
    // フォームのバリデーション
    const forms = document.querySelectorAll('[data-validate]');
    forms.forEach(form => {
      form.addEventListener('submit', (event) => {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
          if (!field.value.trim()) {
            isValid = false;
            field.classList.add('border-red-500');
            
            const label = form.querySelector(`label[for="${field.id}"]`);
            const errorMessage = document.createElement('p');
            errorMessage.className = 'text-red-500 text-sm mt-1';
            errorMessage.textContent = `${label ? label.textContent : '項目'}を入力してください`;
            
            const existingError = field.parentNode.querySelector('.text-red-500');
            if (!existingError) {
              field.parentNode.appendChild(errorMessage);
            }
          } else {
            field.classList.remove('border-red-500');
            const existingError = field.parentNode.querySelector('.text-red-500');
            if (existingError) {
              existingError.remove();
            }
          }
        });
        
        if (!isValid) {
          event.preventDefault();
        }
      });
    });
    
    // 動的テーブルソート
    const sortableTables = document.querySelectorAll('[data-sortable]');
    sortableTables.forEach(table => {
      const headers = table.querySelectorAll('th[data-sort]');
      headers.forEach(header => {
        header.classList.add('cursor-pointer');
        header.addEventListener('click', () => {
          const sortKey = header.getAttribute('data-sort');
          const isNumeric = header.hasAttribute('data-numeric');
          const isDate = header.hasAttribute('data-date');
          
          // 並び替えの方向
          const currentDirection = header.getAttribute('data-direction') || 'asc';
          const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
          
          // 他のヘッダーの状態をリセット
          headers.forEach(h => {
            h.setAttribute('data-direction', '');
            h.querySelector('i')?.remove();
          });
          
          // 現在のヘッダーの状態を更新
          header.setAttribute('data-direction', newDirection);
          
          // ソートアイコンを表示
          const icon = document.createElement('i');
          icon.className = `fas fa-sort-${newDirection === 'asc' ? 'up' : 'down'} ml-1`;
          header.appendChild(icon);
          
          // テーブルの行をソート
          const tbody = table.querySelector('tbody');
          const rows = Array.from(tbody.querySelectorAll('tr'));
          
          rows.sort((a, b) => {
            let aValue = a.querySelector(`td:nth-child(${Array.from(headers).indexOf(header) + 1})`).textContent.trim();
            let bValue = b.querySelector(`td:nth-child(${Array.from(headers).indexOf(header) + 1})`).textContent.trim();
            
            if (isNumeric) {
              aValue = parseFloat(aValue.replace(/[^\d.-]/g, '')) || 0;
              bValue = parseFloat(bValue.replace(/[^\d.-]/g, '')) || 0;
            } else if (isDate) {
              aValue = new Date(aValue).getTime();
              bValue = new Date(bValue).getTime();
            }
            
            if (aValue < bValue) {
              return newDirection === 'asc' ? -1 : 1;
            }
            if (aValue > bValue) {
              return newDirection === 'asc' ? 1 : -1;
            }
            return 0;
          });
          
          // ソート後の行を再配置
          rows.forEach(row => {
            tbody.appendChild(row);
          });
        });
      });
    });
    
    // テーブル検索フィルタ
    const tableFilters = document.querySelectorAll('[data-table-filter]');
    tableFilters.forEach(filter => {
      filter.addEventListener('input', () => {
        const tableId = filter.getAttribute('data-table-filter');
        const table = document.getElementById(tableId);
        if (!table) return;
        
        const rows = table.querySelectorAll('tbody tr');
        const searchTerm = filter.value.toLowerCase();
        
        rows.forEach(row => {
          const text = row.textContent.toLowerCase();
          if (text.includes(searchTerm)) {
            row.style.display = '';
          } else {
            row.style.display = 'none';
          }
        });
      });
    });
  });