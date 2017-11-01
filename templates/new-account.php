<section class="accounts-section accounts-section-create row darkbg">

	<div class="container">

		<div class="wrap">

			<div class="panel">

				<h5 class="title">New Account</h5>

				<div class="text accounts-new-account">

					<form action="/account" method="post"  name="account-creation-form" class="account-creation-form">

						<div class="field field-first">

							<label>First Name</label>

							<input type="text" name="account[first_name]" placeholder="First Name" value="<?php echo $meta['first_name'][0]; ?>">

						</div>

						<div class="field field-last">

							<label>Last Name</label>

							<input type="text" name="account[last_name]" placeholder="Last Name" value="<?php echo $meta['last_name'][0]; ?>">

						</div>

						<div class="field field-email">

							<label>Email</label>

							<input type="email" name="account[email]" placeholder="Email" value="<?php echo $data->data->user_email; ?>" required>

						</div>

						<div class="field field-password">

							<label>Password</label>

							<input type="password" id="password" name="account[password]" placeholder="Password" value="" required>

						</div>

						<div class="field field-password-confirm">

							<label>Confirm Password</label>

							<input type="password" name="account[confirmpassword]" placeholder="Confirm Password" value="" required>

						</div>


						<?php do_action('accounts_account_form_before_submit'); ?>

						<div class="field field-submit">

							<input type="hidden" name="accounts_action" value="accounts_create_account">
							<?php wp_nonce_field( 'accounts' ); ?>

							<button type="submit" name="submit">
								<span class="text">Create Account</span>
								<span class="icon icon-right-open"></span>
							</button>

						</div>

					</form>

				</div>



			</div>

		</div>

	</div>

</section>
