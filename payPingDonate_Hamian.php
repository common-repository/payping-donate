<?php defined('ABSPATH') or die('&lt;h3&gt;Access denied!'); ?>


<div class="wrap">
<div id="icon-edit" class="icon32 icon32-posts-post"><br /></div><h2>حامیان مالی</h2>
    
<form id="posts-filter" action="<?php echo esc_url(payPingDonate_GetCallBackURL()); ?>" method="post">

<p class="search-box">
	<label class="screen-reader-text" for="post-search-input">جست‌وجوی افراد:</label>
	<input type="search" id="post-search-input" name="searchbyname" value="" />
	<input type="submit" name="" id="search-submit" class="button" value="جست‌وجوی افراد"  /></p>

<input type="hidden" id="_wpnonce" name="_wpnonce" value="8aa9aa1697" /><input type="hidden" name="_wp_http_referer" value="/Project/wp-admin/edit.php" />	<div class="tablenav top">

<div class='tablenav-pages one-page'><span class="displaying-num">مبلغ کل حمایت شده :<?php echo esc_html(get_option("payPingDonate_TotalAmount"));?> تومان</span>
</div>
</div>
<input type="hidden" name="mode" value="list" />

		<br class="clear" />
	</div>
<table class="wp-list-table widefat fixed posts" cellspacing="0">
	<thead>
	<tr>
		<th scope='col' id='cb' class='manage-column column-cb check-column'  style=""><label class="screen-reader-text" for="cb-select-all-1">گزینش همه</label><input id="cb-select-all-1" type="checkbox" /></th>
		<th scope='col' id='title' class='manage-column column-title sortable desc'  style="">
		<span>نام و نام خانوادگی</span><span class="sorting-indicator"></span></th>
		<th scope='col' id='author' class='manage-column column-author'  style="">مبلغ (تومان)</th>
		<th scope='col' id='categories' class='manage-column column-categories'  style="">موبایل</th>
		<th scope='col' id='categories' class='manage-column column-categories'  style="">شماره پیگیری</th>
		<th scope='col' id='tags' class='manage-column column-tags'  style="">ایمیل</th>
		<th scope='col' id='comments' class='manage-column column-tags'  style="">توضیحات</th>
		<th scope='col' id='date' class='manage-column column-date sortable asc'  style=""><span>تاریخ</span><span class="sorting-indicator"></span></th>
	</tr>
	</thead>

	<tfoot>
  <tr>
		<th scope='col' id='cb' class='manage-column column-cb check-column'  style=""><label class="screen-reader-text" for="cb-select-all-1">گزینش همه</label><input id="cb-select-all-1" type="checkbox" /></th>
		<th scope='col' id='title' class='manage-column column-title sortable desc'  style="">
		<span>نام و نام خانوادگی</span><span class="sorting-indicator"></span></th>
		<th scope='col' id='author' class='manage-column column-author'  style="">مبلغ (تومان)</th>
		<th scope='col' id='categories' class='manage-column column-categories'  style="">موبایل</th>
        <th scope='col' id='categories' class='manage-column column-categories'  style="">شماره پیگیری</th>
		<th scope='col' id='tags' class='manage-column column-tags'  style="">ایمیل</th>
		<th scope='col' id='comments' class='manage-column column-tags'  style="">توضیحات</th>
		<th scope='col' id='date' class='manage-column column-date sortable asc'  style=""><span>تاریخ</span><span class="sorting-indicator"></span></th>
	</tr>
	</tfoot>

	<tbody id="the-list">
	<?php
		// Initialize variables
		$page = 1;
		$Limit = "";

		// Page handling
		if (isset($_REQUEST['pageid'])) {
			// Sanitize page ID
			$page = intval($_REQUEST['pageid']);
			if ($page < 1) {
				$page = 1;
			}

			$End = $page * 30;
			$Start = $End - 30;

			// Adjust start for pages greater than one
			if ($page > 1) {
				$Start++;
			}

			$Limit = " LIMIT %d, %d";
		}

		// Initialize the global $wpdb object
		global $wpdb;
		$DonateTable = $wpdb->prefix . TABLE_DONATE;

		// Initialize the query and parameters
		$query = "SELECT * FROM `$DonateTable`";
		$query_params = [];

		// Search handling
		if (isset($_REQUEST['searchbyname']) && !empty($_REQUEST['searchbyname'])) {
			// Sanitize search by name
			$SearchName = sanitize_text_field($_REQUEST['searchbyname']);
			$query .= " WHERE `Name` LIKE %s ORDER BY DonateID DESC";
			$query_params[] = '%' . $wpdb->esc_like($SearchName) . '%';
		} else {
			$query .= " ORDER BY DonateID DESC";
		}

		// Add limit clause
		if (!empty($Limit)) {
			$query .= $Limit;
			$query_params[] = $Start;
			$query_params[] = 30; // Items per page
		}

		// Prepare the SQL query
		$prepared_query = $wpdb->prepare($query, $query_params);

		// Execute the query
		$result = $wpdb->get_results($prepared_query, OBJECT);

		// Check for SQL execution errors
		if ($result === false) {
			echo 'Error in query execution.';
			return;
		}
		// Process and display the results
		if (!empty($result)) {
			foreach ($result as $row) : ?>
				<tr id="post-<?php echo esc_attr($row->DonateID); ?>" style="<?php echo esc_attr($row->Status == 'OK' ? 'background-color: #cfc' : ''); ?>" class="post-<?php echo esc_attr($row->DonateID); ?> type-post status-draft format-standard hentry category-news alternate iedit author-self" valign="top">
					<th scope="row" class="check-column">
						<label class="screen-reader-text" for="cb-select-<?php echo esc_attr($row->DonateID); ?>">گزینش رکورد</label>
						<input id="cb-select-<?php echo esc_attr($row->DonateID); ?>" type="checkbox" name="post[]" value="<?php echo esc_attr($row->DonateID); ?>" />
						<div class="locked-indicator"></div>
					</th>
					<td class="post-title page-title column-title">
						<strong><?php echo esc_html($row->Name); ?></strong>
						<small><?php echo esc_html($row->Status); ?></small>
						<div class="locked-info"><span class="locked-avatar"></span> <span class="locked-text"></span></div>
					</td>
					<td class="author column-author"><?php echo esc_html($row->AmountTomaan); ?></td>
					<td class="categories column-categories"><?php echo esc_html($row->Mobile); ?></td>
					<td class="categories column-categories"><?php echo esc_html($row->Authority); ?></td>
					<td class="tags column-tags"><?php echo esc_html($row->Email); ?></td>
					<td class="tags column-tags"><?php echo esc_html($row->Description); ?></td>
					<td class="date column-date"><?php echo esc_html($row->InputDate); ?></td>
				</tr>
			<?php endforeach;
		}
	?>


	</tbody>
</table>
	<div class="tablenav bottom">

		<div class="alignleft actions">
    
	<?php
		// Get the total payment count from the options table
		$totalPay = get_option('payPingDonate_TotalPayment', 0); // Default to 0 if the option is not set

		// Calculate the total number of pages needed
		$PageNumInt = 1;
		if ($totalPay > 0) {
			$PagesNum = $totalPay / 30;
			$PageNumInt = intval($PagesNum);
			if ($PageNumInt < $PagesNum) {
				$PageNumInt++;
			}
		}

		// Determine the current page
		$currentPage = 1;
		if (isset($_REQUEST['pageid'])) {
			$currentPage = intval($_REQUEST['pageid']); // Use intval to directly convert to integer
			if ($currentPage < 1) {
				$currentPage = 1; // Ensure the current page is at least 1
			}
		}
		// echo $PageNumInt;
	?>
    <div class="tablenav-pages"><span class="displaying-num"><?php echo esc_html($totalPay); ?> مورد</span>

    <?php
    for($i = 1 ; $i <= $PageNumInt; $i++)
      {
        if($i == $currentPage)
          echo '<a href="admin.php?page=payPingDonate_Hamian&pageid='. esc_html($i) .'"  class="first-page disabled">'. esc_html($i) .'</a>';
        else
          echo '<a href="admin.php?page=payPingDonate_Hamian&pageid='. esc_html($i) .'"  class="first-page">'. esc_html($i) .'</a>';
      }
    
    ?>

		</div>
		<br class="clear" />
	</div>

</form>

<div id="ajax-response"></div>
<br class="clear" />
</div>
