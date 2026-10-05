/* ==========================================================================
   Practical 6: Rendering External JSON Data using Fetch API, Search & Filter
   Modular JavaScript — ES6+ Features
   Skills: JSON, Fetch API, arrays, map/filter/sort, pagination, localStorage
   ========================================================================== */

// ==================== MODULE: State Management ====================
const AppState = {
  currentTab: 'events',
  data: { events: [], students: [], faqs: [] },
  filteredData: [],
  currentPage: 1,
  itemsPerPage: 6,
  searchQuery: '',
  filterValue: 'all',
  sortField: 'default',
  sortOrder: 'asc',
  isLoading: false,
  error: null,
  // Dependent dropdown state
  selectedCountry: 'all',
  selectedState: 'all',
  selectedCity: 'all',
};

// ==================== MODULE: Configuration ====================
const Config = {
  dataFiles: {
    events: 'data/events.json',
    students: 'data/students.json',
    faqs: 'data/faqs.json',
  },
  filters: {
    events: [
      { value: 'all', label: 'All Categories' },
      { value: 'Academic', label: 'Academic' },
      { value: 'Cultural', label: 'Cultural' },
      { value: 'Sports', label: 'Sports' },
      { value: 'Technical', label: 'Technical' },
      { value: 'Workshop', label: 'Workshop' },
    ],
    students: [
      { value: 'all', label: 'All Departments' },
      { value: 'Computer Engineering', label: 'Computer Engg.' },
      { value: 'IT Engineering', label: 'IT Engg.' },
      { value: 'Electrical Engineering', label: 'Electrical Engg.' },
      { value: 'Mechanical Engineering', label: 'Mechanical Engg.' },
      { value: 'Civil Engineering', label: 'Civil Engg.' },
    ],
    faqs: [
      { value: 'all', label: 'All Categories' },
      { value: 'Admission', label: 'Admission' },
      { value: 'Examination', label: 'Examination' },
      { value: 'Fees', label: 'Fees' },
      { value: 'Hostel', label: 'Hostel' },
      { value: 'Placement', label: 'Placement' },
    ],
  },
  sortOptions: {
    events: [
      { value: 'default', label: 'Sort By' },
      { value: 'title', label: 'Title' },
      { value: 'date', label: 'Date' },
      { value: 'category', label: 'Category' },
    ],
    students: [
      { value: 'default', label: 'Sort By' },
      { value: 'name', label: 'Name' },
      { value: 'cgpa', label: 'CGPA' },
      { value: 'semester', label: 'Semester' },
      { value: 'department', label: 'Department' },
    ],
    faqs: [
      { value: 'default', label: 'Sort By' },
      { value: 'priority', label: 'Priority' },
      { value: 'helpful', label: 'Helpful Count' },
      { value: 'category', label: 'Category' },
    ],
  },
  searchPlaceholders: {
    events: 'Search events by title, description, location...',
    students: 'Search students by name, department, skills...',
    faqs: 'Search FAQs by question or answer...',
  },
  // Dependent dropdown data (Intermediate Extension)
  locationData: {
    India: {
      Gujarat: ['Ahmedabad', 'Surat', 'Vadodara', 'Rajkot', 'Anand', 'Gandhinagar'],
      Maharashtra: ['Mumbai', 'Pune', 'Nagpur', 'Nashik'],
      Rajasthan: ['Jaipur', 'Udaipur', 'Jodhpur'],
      Karnataka: ['Bangalore', 'Mysore', 'Hubli'],
      'Tamil Nadu': ['Chennai', 'Coimbatore', 'Madurai'],
    },
  },
};

// ==================== MODULE: DOM Elements ====================
const DOM = {
  tabs: () => document.querySelectorAll('.p6-tab'),
  searchInput: () => document.getElementById('search-input'),
  filterSelect: () => document.getElementById('filter-select'),
  sortSelect: () => document.getElementById('sort-select'),
  sortOrderBtn: () => document.getElementById('sort-order-btn'),
  dataContainer: () => document.getElementById('data-container'),
  resultsCount: () => document.getElementById('results-count'),
  loadingSpinner: () => document.getElementById('loading-spinner'),
  errorMessage: () => document.getElementById('error-message'),
  paginationControls: () => document.getElementById('pagination-controls'),
  pageNumbers: () => document.getElementById('page-numbers'),
  btnFirst: () => document.getElementById('btn-first'),
  btnPrev: () => document.getElementById('btn-prev'),
  btnNext: () => document.getElementById('btn-next'),
  btnLast: () => document.getElementById('btn-last'),
  // Dependent dropdowns
  dependentDropdowns: () => document.getElementById('dependent-dropdowns'),
  countrySelect: () => document.getElementById('country-select'),
  stateSelect: () => document.getElementById('state-select'),
  citySelect: () => document.getElementById('city-select'),
  // Cache
  cacheStatus: () => document.getElementById('cache-status'),
  btnLoadCache: () => document.getElementById('btn-load-cache'),
  btnClearCache: () => document.getElementById('btn-clear-cache'),
  // Banner
  bannerCloseBtn: () => document.querySelector('.banner-close-btn'),
  banner: () => document.querySelector('.notification-banner'),
  // Mobile menu
  mobileMenuBtn: () => document.querySelector('.mobile-menu-btn'),
  mainNav: () => document.querySelector('.main-nav'),
};

// ==================== MODULE: Fetch & Data Loading ====================
async function fetchJSON(url) {
  console.log(`[Fetch] Loading: ${url}`);
  const response = await fetch(url);
  if (!response.ok) {
    throw new Error(`HTTP Error ${response.status}: Failed to fetch ${url}`);
  }
  const data = await response.json();
  console.log(`[Fetch] Loaded ${data.length} records from ${url}`);
  return data;
}

async function loadData(type) {
  setLoading(true);
  setError(null);

  try {
    // Try fetching from network first
    const data = await fetchJSON(Config.dataFiles[type]);
    AppState.data[type] = data;

    // Cache to localStorage (Advanced Extension)
    CacheManager.save(type, data);

    processAndRender();
  } catch (err) {
    console.error(`[Error] Failed to load ${type}:`, err);

    // Try loading from cache as fallback
    const cachedData = CacheManager.load(type);
    if (cachedData) {
      console.log(`[Cache] Using cached data for ${type}`);
      AppState.data[type] = cachedData;
      processAndRender();
      setError(`⚠️ Loaded from cache (network unavailable). Data may not be latest.`);
    } else {
      setError(`❌ Failed to load ${type} data. ${err.message}`);
    }
  } finally {
    setLoading(false);
  }
}

// ==================== MODULE: Data Processing (Filter, Search, Sort) ====================
function processData() {
  const type = AppState.currentTab;
  let data = [...AppState.data[type]];

  // 1. Search — using filter() and includes()
  if (AppState.searchQuery) {
    const query = AppState.searchQuery.toLowerCase();
    data = data.filter(item => {
      switch (type) {
        case 'events':
          return (
            item.title.toLowerCase().includes(query) ||
            item.description.toLowerCase().includes(query) ||
            item.location.toLowerCase().includes(query) ||
            item.organizer.toLowerCase().includes(query)
          );
        case 'students':
          return (
            item.name.toLowerCase().includes(query) ||
            item.department.toLowerCase().includes(query) ||
            item.enrollmentNo.toLowerCase().includes(query) ||
            item.skills.some(s => s.toLowerCase().includes(query))
          );
        case 'faqs':
          return (
            item.question.toLowerCase().includes(query) ||
            item.answer.toLowerCase().includes(query)
          );
        default:
          return true;
      }
    });
  }

  // 2. Filter — using filter()
  if (AppState.filterValue !== 'all') {
    data = data.filter(item => {
      switch (type) {
        case 'events': return item.category === AppState.filterValue;
        case 'students': return item.department === AppState.filterValue;
        case 'faqs': return item.category === AppState.filterValue;
        default: return true;
      }
    });
  }

  // 3. Dependent dropdown filter (Students only - Intermediate Extension)
  if (type === 'students') {
    if (AppState.selectedCountry !== 'all') {
      data = data.filter(item => item.country === AppState.selectedCountry);
    }
    if (AppState.selectedState !== 'all') {
      data = data.filter(item => item.state === AppState.selectedState);
    }
    if (AppState.selectedCity !== 'all') {
      data = data.filter(item => item.city === AppState.selectedCity);
    }
  }

  // 4. Sort — using sort()
  if (AppState.sortField !== 'default') {
    const priorityOrder = { high: 1, medium: 2, low: 3 };
    data.sort((a, b) => {
      let valA, valB;

      switch (AppState.sortField) {
        case 'title':
        case 'name':
        case 'category':
        case 'department':
          valA = (a[AppState.sortField] || '').toLowerCase();
          valB = (b[AppState.sortField] || '').toLowerCase();
          return AppState.sortOrder === 'asc'
            ? valA.localeCompare(valB)
            : valB.localeCompare(valA);

        case 'date':
          valA = new Date(a.date);
          valB = new Date(b.date);
          return AppState.sortOrder === 'asc' ? valA - valB : valB - valA;

        case 'cgpa':
        case 'semester':
        case 'helpful':
          valA = a[AppState.sortField];
          valB = b[AppState.sortField];
          return AppState.sortOrder === 'asc' ? valA - valB : valB - valA;

        case 'priority':
          valA = priorityOrder[a.priority] || 99;
          valB = priorityOrder[b.priority] || 99;
          return AppState.sortOrder === 'asc' ? valA - valB : valB - valA;

        default:
          return 0;
      }
    });
  }

  AppState.filteredData = data;
  return data;
}

// ==================== MODULE: Rendering ====================
function renderCards() {
  const container = DOM.dataContainer();
  const data = AppState.filteredData;
  const type = AppState.currentTab;

  // Pagination slice
  const start = (AppState.currentPage - 1) * AppState.itemsPerPage;
  const end = start + AppState.itemsPerPage;
  const pageData = data.slice(start, end);

  if (pageData.length === 0) {
    container.innerHTML = `
      <div class="p6-no-results">
        <span class="p6-no-results-icon">🔍</span>
        <h3>No Results Found</h3>
        <p>Try adjusting your search or filter criteria.</p>
      </div>
    `;
    return;
  }

  // Use map() to transform data into HTML cards
  const cardsHTML = pageData.map(item => {
    switch (type) {
      case 'events': return renderEventCard(item);
      case 'students': return renderStudentCard(item);
      case 'faqs': return renderFAQCard(item);
      default: return '';
    }
  }).join('');

  container.innerHTML = cardsHTML;
}

function renderEventCard(event) {
  const categoryClass = `p6-badge-${event.category.toLowerCase()}`;
  const dateObj = new Date(event.date);
  const formattedDate = dateObj.toLocaleDateString('en-IN', {
    day: 'numeric', month: 'short', year: 'numeric',
  });
  const regStatus = event.registrationOpen
    ? '<span style="color: var(--success);">✅ Open</span>'
    : '<span style="color: var(--danger);">❌ Closed</span>';

  return `
    <article class="p6-card" tabindex="0">
      <div class="p6-card-header">
        <h3 class="p6-card-title">${escapeHTML(event.title)}</h3>
        <span class="p6-card-badge ${categoryClass}">${escapeHTML(event.category)}</span>
      </div>
      <div class="p6-card-body">
        <p class="p6-card-desc">${escapeHTML(event.description)}</p>
      </div>
      <div class="p6-card-meta">
        <span>📅 ${formattedDate}</span>
        <span>🕐 ${escapeHTML(event.time)}</span>
        <span>📍 ${escapeHTML(event.location)}</span>
        <span>🏢 ${escapeHTML(event.organizer)}</span>
        <span>Registration: ${regStatus}</span>
      </div>
    </article>
  `;
}

function renderStudentCard(student) {
  const cgpaClass = student.cgpa >= 8.0 ? 'p6-cgpa-high' : student.cgpa >= 6.5 ? 'p6-cgpa-mid' : 'p6-cgpa-low';
  const skillsHTML = student.skills
    .map(skill => `<span class="p6-skill-tag">${escapeHTML(skill)}</span>`)
    .join('');

  return `
    <article class="p6-card p6-student-card" tabindex="0">
      <img src="${escapeHTML(student.profileImage)}" alt="${escapeHTML(student.name)}" class="p6-student-avatar" loading="lazy">
      <h3 class="p6-card-title">${escapeHTML(student.name)}</h3>
      <div class="p6-cgpa ${cgpaClass}">${student.cgpa.toFixed(2)}</div>
      <div class="p6-student-info">
        <span>🎓 ${escapeHTML(student.department)}</span>
        <span>📚 Semester ${student.semester}</span>
        <span>🆔 ${escapeHTML(student.enrollmentNo)}</span>
        <span>📍 ${escapeHTML(student.city)}, ${escapeHTML(student.state)}</span>
      </div>
      <div class="p6-skills">${skillsHTML}</div>
    </article>
  `;
}

function renderFAQCard(faq) {
  const categoryClass = `p6-badge-${faq.category.toLowerCase()}`;
  const priorityClass = `p6-badge-${faq.priority}`;

  return `
    <article class="p6-card p6-faq-card" tabindex="0">
      <div class="p6-card-header">
        <h3 class="p6-card-title">❓ ${escapeHTML(faq.question)}</h3>
      </div>
      <div class="p6-card-body">
        <p class="p6-card-desc">${escapeHTML(faq.answer)}</p>
        <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
          <span class="p6-card-badge ${categoryClass}">${escapeHTML(faq.category)}</span>
          <span class="p6-card-badge ${priorityClass}">${faq.priority} priority</span>
        </div>
      </div>
      <div class="p6-faq-helpful">
        <span>👍 ${faq.helpful} helpful</span>
        <span>👎 ${faq.notHelpful} not helpful</span>
      </div>
    </article>
  `;
}

// ==================== MODULE: Pagination ====================
function renderPagination() {
  const totalItems = AppState.filteredData.length;
  const totalPages = Math.ceil(totalItems / AppState.itemsPerPage);
  const currentPage = AppState.currentPage;

  DOM.btnFirst().disabled = currentPage <= 1;
  DOM.btnPrev().disabled = currentPage <= 1;
  DOM.btnNext().disabled = currentPage >= totalPages;
  DOM.btnLast().disabled = currentPage >= totalPages;

  // Generate page number buttons
  const pageNumbersContainer = DOM.pageNumbers();
  pageNumbersContainer.innerHTML = '';

  // Show max 5 page buttons
  let startPage = Math.max(1, currentPage - 2);
  let endPage = Math.min(totalPages, startPage + 4);
  if (endPage - startPage < 4) {
    startPage = Math.max(1, endPage - 4);
  }

  for (let i = startPage; i <= endPage; i++) {
    const btn = document.createElement('button');
    btn.className = `p6-page-btn${i === currentPage ? ' active' : ''}`;
    btn.textContent = i;
    btn.setAttribute('aria-label', `Page ${i}`);
    if (i === currentPage) btn.setAttribute('aria-current', 'page');
    btn.addEventListener('click', () => goToPage(i));
    pageNumbersContainer.appendChild(btn);
  }
}

function goToPage(page) {
  const totalPages = Math.ceil(AppState.filteredData.length / AppState.itemsPerPage);
  AppState.currentPage = Math.max(1, Math.min(page, totalPages));
  renderCards();
  renderPagination();
  updateResultsCount();
  // Scroll to top of data
  DOM.dataContainer().scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ==================== MODULE: UI Updates ====================
function setLoading(isLoading) {
  AppState.isLoading = isLoading;
  const spinner = DOM.loadingSpinner();
  if (spinner) spinner.style.display = isLoading ? 'flex' : 'none';
}

function setError(message) {
  AppState.error = message;
  const errorEl = DOM.errorMessage();
  if (errorEl) {
    errorEl.style.display = message ? 'block' : 'none';
    errorEl.textContent = message || '';
  }
}

function updateResultsCount() {
  const total = AppState.filteredData.length;
  const start = Math.min((AppState.currentPage - 1) * AppState.itemsPerPage + 1, total);
  const end = Math.min(AppState.currentPage * AppState.itemsPerPage, total);
  const countEl = DOM.resultsCount();
  if (countEl) {
    countEl.textContent = total === 0
      ? 'No results found'
      : `Showing ${start}–${end} of ${total} ${AppState.currentTab}`;
  }
}

function updateControls() {
  const type = AppState.currentTab;

  // Update search placeholder
  const searchInput = DOM.searchInput();
  if (searchInput) {
    searchInput.placeholder = Config.searchPlaceholders[type];
    searchInput.value = AppState.searchQuery;
  }

  // Update filter dropdown
  const filterSelect = DOM.filterSelect();
  if (filterSelect) {
    filterSelect.innerHTML = Config.filters[type]
      .map(opt => `<option value="${opt.value}">${opt.label}</option>`)
      .join('');
    filterSelect.value = AppState.filterValue;
  }

  // Update sort dropdown
  const sortSelect = DOM.sortSelect();
  if (sortSelect) {
    sortSelect.innerHTML = Config.sortOptions[type]
      .map(opt => `<option value="${opt.value}">${opt.label}</option>`)
      .join('');
    sortSelect.value = AppState.sortField;
  }

  // Update sort order button
  const sortOrderBtn = DOM.sortOrderBtn();
  if (sortOrderBtn) {
    sortOrderBtn.textContent = AppState.sortOrder === 'asc' ? '↑ Asc' : '↓ Desc';
  }

  // Show/hide dependent dropdowns (only for Students tab)
  const depDropdowns = DOM.dependentDropdowns();
  if (depDropdowns) {
    depDropdowns.style.display = type === 'students' ? 'block' : 'none';
  }
}

function processAndRender() {
  processData();
  AppState.currentPage = 1;
  renderCards();
  renderPagination();
  updateResultsCount();
}

// ==================== MODULE: Dependent Dropdowns (Intermediate Extension) ====================
function initDependentDropdowns() {
  const countrySelect = DOM.countrySelect();
  const stateSelect = DOM.stateSelect();
  const citySelect = DOM.citySelect();

  if (!countrySelect || !stateSelect || !citySelect) return;

  // Populate countries
  countrySelect.innerHTML = '<option value="all">All Countries</option>';
  Object.keys(Config.locationData).forEach(country => {
    countrySelect.innerHTML += `<option value="${country}">${country}</option>`;
  });

  // Country change handler
  countrySelect.addEventListener('change', (e) => {
    const country = e.target.value;
    AppState.selectedCountry = country;
    AppState.selectedState = 'all';
    AppState.selectedCity = 'all';

    // Populate states
    stateSelect.innerHTML = '<option value="all">All States</option>';
    citySelect.innerHTML = '<option value="all">All Cities</option>';

    if (country !== 'all' && Config.locationData[country]) {
      stateSelect.disabled = false;
      Object.keys(Config.locationData[country]).forEach(state => {
        stateSelect.innerHTML += `<option value="${state}">${state}</option>`;
      });
    } else {
      stateSelect.disabled = true;
      citySelect.disabled = true;
    }

    processAndRender();
  });

  // State change handler
  stateSelect.addEventListener('change', (e) => {
    const state = e.target.value;
    AppState.selectedState = state;
    AppState.selectedCity = 'all';

    // Populate cities
    citySelect.innerHTML = '<option value="all">All Cities</option>';

    const country = AppState.selectedCountry;
    if (state !== 'all' && Config.locationData[country] && Config.locationData[country][state]) {
      citySelect.disabled = false;
      Config.locationData[country][state].forEach(city => {
        citySelect.innerHTML += `<option value="${city}">${city}</option>`;
      });
    } else {
      citySelect.disabled = true;
    }

    processAndRender();
  });

  // City change handler
  citySelect.addEventListener('change', (e) => {
    AppState.selectedCity = e.target.value;
    processAndRender();
  });
}

// ==================== MODULE: Cache Manager (Advanced Extension) ====================
const CacheManager = {
  prefix: 'studenthub_p6_',

  save(type, data) {
    try {
      const cacheEntry = {
        data: data,
        timestamp: new Date().toISOString(),
        count: data.length,
      };
      localStorage.setItem(this.prefix + type, JSON.stringify(cacheEntry));
      console.log(`[Cache] Saved ${data.length} ${type} records to localStorage`);
      this.updateStatus();
    } catch (e) {
      console.warn('[Cache] Failed to save to localStorage:', e.message);
    }
  },

  load(type) {
    try {
      const raw = localStorage.getItem(this.prefix + type);
      if (!raw) return null;
      const cacheEntry = JSON.parse(raw);
      console.log(`[Cache] Loaded ${cacheEntry.count} ${type} records from localStorage (cached at ${cacheEntry.timestamp})`);
      return cacheEntry.data;
    } catch (e) {
      console.warn('[Cache] Failed to read from localStorage:', e.message);
      return null;
    }
  },

  clear() {
    ['events', 'students', 'faqs'].forEach(type => {
      localStorage.removeItem(this.prefix + type);
    });
    console.log('[Cache] All cached data cleared');
    this.updateStatus();
  },

  getInfo() {
    const info = [];
    ['events', 'students', 'faqs'].forEach(type => {
      const raw = localStorage.getItem(this.prefix + type);
      if (raw) {
        const entry = JSON.parse(raw);
        const date = new Date(entry.timestamp);
        info.push({
          type,
          count: entry.count,
          cachedAt: date.toLocaleString('en-IN'),
          size: new Blob([raw]).size,
        });
      }
    });
    return info;
  },

  updateStatus() {
    const statusEl = DOM.cacheStatus();
    if (!statusEl) return;

    const info = this.getInfo();
    if (info.length === 0) {
      statusEl.textContent = 'Cache: Empty — No data cached';
      return;
    }

    const totalRecords = info.reduce((sum, i) => sum + i.count, 0);
    const totalSize = info.reduce((sum, i) => sum + i.size, 0);
    const sizeKB = (totalSize / 1024).toFixed(1);
    const types = info.map(i => `${i.type}(${i.count})`).join(', ');
    statusEl.textContent = `Cache: ${totalRecords} records (${sizeKB} KB) — ${types}`;
  },

  loadAllFromCache() {
    let loaded = false;
    ['events', 'students', 'faqs'].forEach(type => {
      const data = this.load(type);
      if (data) {
        AppState.data[type] = data;
        loaded = true;
      }
    });
    if (loaded) {
      processAndRender();
      console.log('[Cache] Loaded all data from cache');
    } else {
      alert('No cached data found. Please load data from the network first.');
    }
  },
};

// ==================== MODULE: Utility Functions ====================
function escapeHTML(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

function debounce(func, delay) {
  let timeoutId;
  return (...args) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => func.apply(null, args), delay);
  };
}

// ==================== MODULE: Event Handlers ====================
function setupEventListeners() {
  // Tab switching
  DOM.tabs().forEach(tab => {
    tab.addEventListener('click', () => {
      const target = tab.dataset.target;
      if (target === AppState.currentTab) return;

      // Update tab UI
      DOM.tabs().forEach(t => {
        t.classList.remove('active');
        t.setAttribute('aria-selected', 'false');
      });
      tab.classList.add('active');
      tab.setAttribute('aria-selected', 'true');

      // Reset state
      AppState.currentTab = target;
      AppState.searchQuery = '';
      AppState.filterValue = 'all';
      AppState.sortField = 'default';
      AppState.sortOrder = 'asc';
      AppState.currentPage = 1;
      AppState.selectedCountry = 'all';
      AppState.selectedState = 'all';
      AppState.selectedCity = 'all';

      updateControls();

      // Load data if not already loaded
      if (AppState.data[target].length === 0) {
        loadData(target);
      } else {
        processAndRender();
      }
    });
  });

  // Search with debounce
  const searchInput = DOM.searchInput();
  if (searchInput) {
    searchInput.addEventListener('input', debounce((e) => {
      AppState.searchQuery = e.target.value.trim();
      processAndRender();
    }, 300));
  }

  // Filter
  const filterSelect = DOM.filterSelect();
  if (filterSelect) {
    filterSelect.addEventListener('change', (e) => {
      AppState.filterValue = e.target.value;
      processAndRender();
    });
  }

  // Sort
  const sortSelect = DOM.sortSelect();
  if (sortSelect) {
    sortSelect.addEventListener('change', (e) => {
      AppState.sortField = e.target.value;
      processAndRender();
    });
  }

  // Sort order toggle
  const sortOrderBtn = DOM.sortOrderBtn();
  if (sortOrderBtn) {
    sortOrderBtn.addEventListener('click', () => {
      AppState.sortOrder = AppState.sortOrder === 'asc' ? 'desc' : 'asc';
      sortOrderBtn.textContent = AppState.sortOrder === 'asc' ? '↑ Asc' : '↓ Desc';
      processAndRender();
    });
  }

  // Pagination buttons
  DOM.btnFirst()?.addEventListener('click', () => goToPage(1));
  DOM.btnPrev()?.addEventListener('click', () => goToPage(AppState.currentPage - 1));
  DOM.btnNext()?.addEventListener('click', () => goToPage(AppState.currentPage + 1));
  DOM.btnLast()?.addEventListener('click', () => {
    const totalPages = Math.ceil(AppState.filteredData.length / AppState.itemsPerPage);
    goToPage(totalPages);
  });

  // Cache buttons (Advanced Extension)
  DOM.btnClearCache()?.addEventListener('click', () => {
    if (confirm('Clear all cached data?')) {
      CacheManager.clear();
    }
  });

  DOM.btnLoadCache()?.addEventListener('click', () => {
    CacheManager.loadAllFromCache();
  });

  // Banner close
  const bannerCloseBtn = DOM.bannerCloseBtn();
  if (bannerCloseBtn) {
    bannerCloseBtn.addEventListener('click', () => {
      const banner = DOM.banner();
      if (banner) banner.style.display = 'none';
    });
  }

  // Mobile menu toggle
  const mobileMenuBtn = DOM.mobileMenuBtn();
  if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', () => {
      const nav = DOM.mainNav();
      if (nav) nav.classList.toggle('active');
      const expanded = mobileMenuBtn.getAttribute('aria-expanded') === 'true';
      mobileMenuBtn.setAttribute('aria-expanded', !expanded);
    });
  }
}

// ==================== MODULE: Initialization ====================
function init() {
  console.log('[App] Initializing Practical 6 — Data Explorer');

  // Setup all event listeners
  setupEventListeners();

  // Initialize dependent dropdowns
  initDependentDropdowns();

  // Update controls for initial tab
  updateControls();

  // Update cache status
  CacheManager.updateStatus();

  // Load initial data (Events tab)
  loadData('events');

  console.log('[App] Initialization complete');
}

// Start the application when DOM is ready
document.addEventListener('DOMContentLoaded', init);
