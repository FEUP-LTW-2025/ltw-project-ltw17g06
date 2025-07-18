<?php 
require_once __DIR__ . '/../utils/session.php';
require_once '../utils/csrf.php';
function drawHeader() { 
  $session = Session::getInstance();
  $loggedIn = $session->isLoggedIn();
  $user = $session->getUser();
  $csrf_token = CSRF::getToken();
?>
<header>
  <div>
    <a href="../index.php">
      <img src="../assets/logo-w.png" id="logo" alt="sixer" />
    </a>
    <!-- should only be visible is user is logged in (this is header alignment) -->
      <?php if ($loggedIn): ?>
        <div class="spacer" style="width: 10vw; display: inline-block;"></div>
      <?php endif; ?>
  </div>
  <div class="searchbar">
    <a href="#" id="filters-btn">
      <span class="filters-text">filters</span>
      <img class="icon" src="../assets/icons/dropdown.svg" alt="" />
    </a>
    <form action="search.php" method="get">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>" />
      <?php $search_value = isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>
      <input
        type="text"
        name="q"
        id="main-search"
        placeholder="what do you need to get done?"
        value="<?= $search_value ?>"
      />
      <button type="submit">
        <img class="icon" src="../assets/icons/search.svg" alt="search" />
      </button>
    </form>
  </div>
  <div class="account">
    <?php if (
      $loggedIn && isset($user['user_id'])
    ): ?>
      <a href="profile.php?id=<?= urlencode($user['user_id']) ?>" class="profile-link-with-pic">
        <p style="margin:0;">profile</p>
        <img src="../action/get_profile_picture.php?id=<?= urlencode($user['user_id']) ?>" alt="profile picture" class="header-profile-pic" />
      </a>
      <?php if (isset($user['access_level']) && $user['access_level'] === 'admin'): ?>
      <a href="admin.php" class="simple-button admin-link">
        [admin]
      </a>
      <?php endif; ?>
      <a href="create_service.php" class="simple-button">
        <span class="new-service-plus">+</span>
        <span class="new-service-text"> new service</span>
      </a>
      <a href="../action/logout.php" class="simple-button">sign out</a>
    <?php else: ?>
      <a href="signup.php">sign-up</a>
      <a href="login.php" class="simple-button">login</a>
    <?php endif; ?>
  </div>
</header>
<div id="filters-modal" class="filters-modal">
  <div class="filters-modal-content">
    <button id="close-filters" type="button">&times;</button>
    <h3>Filters</h3>
    <form id="filters-form">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>" />
      <label for="filter-category">Category:</label>
      <select id="filter-category" name="category">
        <option value="">Any</option>
        <?php
          require_once __DIR__ . '/../utils/database.php';
          $db = Database::getInstance();
          $stmt = $db->query('SELECT category_name FROM categories ORDER BY category_name');
          $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
          foreach ($categories as $cat) {
            $selected = (isset($_GET['category']) && $_GET['category'] === $cat) ? 'selected' : '';
            echo '<option value="' . htmlspecialchars($cat) . '" ' . $selected . '>' . htmlspecialchars($cat) . '</option>';
          }
        ?>
      </select>
      <br /><br />
      <label>Price:</label>
      <div class="price-fields">
        <input type="number" id="min-price" name="min_price" placeholder="Min" min="0" />
        <input type="number" id="max-price" name="max_price" placeholder="Max" min="0" />
      </div>
      <br /><br />
      <label>Rating:</label>
      <div class="price-fields">
        <input type="number" id="min-rating" name="min_rating" placeholder="Min" min="0" max="5" step="0.1" />
        <input type="number" id="max-rating" name="max_rating" placeholder="Max" min="0" max="5" step="0.1" />
      </div>
      <br /><br />
      <button type="submit" class="simple-button">Apply Filters</button>
    </form>
  </div>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const minRatingInput = document.getElementById('min-rating');
    const maxRatingInput = document.getElementById('max-rating');
    
    if (minRatingInput && maxRatingInput) {
      const validateRatings = function() {
        const minVal = parseFloat(minRatingInput.value) || 0;
        const maxVal = parseFloat(maxRatingInput.value) || 5;
        
        if (minVal > maxVal && maxVal !== '') {
          minRatingInput.value = maxVal;
          
          minRatingInput.style.transition = 'background-color 0.3s';
          minRatingInput.style.backgroundColor = '#ff8c8c';
          setTimeout(() => {
            minRatingInput.style.backgroundColor = '';
          }, 500);
        }
      };
      
      minRatingInput.addEventListener('input', validateRatings);
      minRatingInput.addEventListener('change', validateRatings);
      maxRatingInput.addEventListener('input', validateRatings);
      maxRatingInput.addEventListener('change', validateRatings);
    }
    
    const minPriceInput = document.getElementById('min-price');
    const maxPriceInput = document.getElementById('max-price');
    
    if (minPriceInput && maxPriceInput) {
      const validatePrices = function() {
        const minVal = parseFloat(minPriceInput.value) || 0;
        const maxVal = parseFloat(maxPriceInput.value);
        
        if (maxPriceInput.value.trim() !== '' && minVal > maxVal) {
          minPriceInput.value = maxVal;
          
          minPriceInput.style.transition = 'background-color 0.3s';
          minPriceInput.style.backgroundColor = '#ff8c8c';
          setTimeout(() => {
            minPriceInput.style.backgroundColor = '';
          }, 500);
        }
      };
      
      minPriceInput.addEventListener('input', validatePrices);
      minPriceInput.addEventListener('change', validatePrices);
      maxPriceInput.addEventListener('input', validatePrices);
      maxPriceInput.addEventListener('change', validatePrices);
    }
    
    var filtersBtn = document.getElementById('filters-btn');
    var filtersModal = document.getElementById('filters-modal');
    var closeFilters = document.getElementById('close-filters');
    if (filtersBtn && filtersModal && closeFilters) {

      filtersBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        const urlParams = new URLSearchParams(window.location.search);
        
        const category = urlParams.get('category');
        if (category) {
          document.getElementById('filter-category').value = category;
        }
        
        const minPrice = urlParams.get('min_price');
        const maxPrice = urlParams.get('max_price');
        if (minPrice) {
          document.querySelector('input[name="min_price"]').value = minPrice;
        }
        if (maxPrice) {
          document.querySelector('input[name="max_price"]').value = maxPrice;
        }
        
        const minRating = urlParams.get('min_rating');
        const maxRating = urlParams.get('max_rating');
        if (minRating) {
          document.querySelector('input[name="min_rating"]').value = minRating;
        }
        if (maxRating) {
          document.querySelector('input[name="max_rating"]').value = maxRating;
        }
        
        filtersModal.style.display = 'flex';
      });
      
      closeFilters.addEventListener('click', function() {
        filtersModal.style.display = 'none';
      });
      filtersModal.addEventListener('mousedown', function(event) {
        if (event.target === filtersModal) {
          filtersModal.style.display = 'none';
        }
      });
    }
  });

  document.getElementById('filters-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const urlParams = new URLSearchParams(window.location.search);
    const currentQuery = urlParams.get('q');
    
    const q = currentQuery || (document.getElementById('main-search') ? document.getElementById('main-search').value : '');
    const category = document.getElementById('filter-category').value;
    const min_price = this.elements['min_price'].value.trim();
    const max_price = this.elements['max_price'].value.trim();
    const min_rating = this.elements['min_rating'].value.trim();
    const max_rating = this.elements['max_rating'].value.trim();

    let url = 'search.php?';
    if (q) url += 'q=' + encodeURIComponent(q) + '&';
    if (category) url += 'category=' + encodeURIComponent(category) + '&';
    if (min_price) url += 'min_price=' + encodeURIComponent(min_price) + '&';
    if (max_price) url += 'max_price=' + encodeURIComponent(max_price) + '&';
    if (min_rating) url += 'min_rating=' + encodeURIComponent(min_rating) + '&';
    if (max_rating) url += 'max_rating=' + encodeURIComponent(max_rating) + '&';
    
    if (url.endsWith('&')) {
      url = url.slice(0, -1);
    }
    
    window.location.href = url;
});
</script>
<?php } ?>
