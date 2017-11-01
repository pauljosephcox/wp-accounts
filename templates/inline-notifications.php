<?php if(!empty($notifications[0])) : ?>

	<div class="inline-notifications">

		<?php foreach($notifications as $notification) : ?>

			<a href="<?php echo $notification['link']; ?>" class="button-primary inline-notification">

				<div class="text"><?php echo $notification['message']; ?></div>

			</a>

		<?php endforeach; ?>

	</div>

<?php endif; ?>