<?php global $wpmember_accounts; if(!empty($wpmember_accounts->errors)){ $notification['status'] = 'bad'; }?>
<section class="accounts-notification <?php echo $notification['status']; ?>">

	<div class="container">

		<div class="wrap">

			<p><?php echo $notification['message']; ?></p>

			<?php if(!empty($wpmember_accounts->errors)) : foreach($wpmember_accounts->errors as $error) : ?>

				<p><?php echo $error; ?></p>

			<?php endforeach; endif; ?>

		</div>

	</div>

</section>