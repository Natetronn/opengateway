<?

/* Default Values */

if (!isset($form)) {
	$form = array(
				'first_name' => '',
				'last_name' => '',
				'company' => '',
				'address_1' => '',
				'address_2' => '',
				'city' => '',
				'state' => '',
				'postal_code' => '',
				'country' => '840',
				'suspended' => '0',
				'gmt_offset' => 'UM5',
				'phone' => '',
				'email' => '',
				'username' => '',
				'client_type' => '2'	
			);
} ?>
<?=$this->load->view('cp/header', array('head_files' => '<script type="text/javascript" src="' . site_url('js/form.client.js') . '"></script>'));?>
<h1><?=$form_title;?></h1>
<form class="form" id="form_email" method="post" action="<?=site_url($form_action);?>">
<fieldset>
	<legend>System Information</legend>
	<ul class="form">
		<li>
			<label for="username" class="full">Username</labe>
		</li>
		<li>
			<input type="text" class="text required full" id="username" name="username" value="<?=$form['username'];?>" />
		</li>
		<li>
			<label for="email" class="full">Email Address</label>
		</li>
		<li>
			<input type="text" class="text required full email" id="email" name="email" value="<?=$form['email'];?>" />
		</li>
		<li>
			<label for="password" class="full">Password</label>
		</li>
		<li>
			<input type="password" class="text <? if ($action == 'new') { ?>required<? } ?> full" id="password" name="password" value="" />
		</li>
		<li>
			<label for="password2" class="full">Repeat Password</label>
		</li>
		<li>
			<input type="password" class="text <? if ($action == 'new') { ?>required<? } ?> full" id="password2" name="password2" value="" />
		</li>
	</ul>
</fieldset>
<fieldset>
	<legend>Personal Information</legend>
	<ul class="form">
		<li>
			<label for="first_name">Name</label>
			<input class="text required" type="text" id="first_name" name="first_name" value="<?=$form['first_name'];?>" />&nbsp;&nbsp;<label style="display:none" for="last_name">Last Name</label><input class="text required" type="text" id="last_name" name="last_name" value="<?=$form['last_name'];?>" />
		</li>
		<li>
			<label for="company">Company</label>
			<input class="text" type="text required" id="company" name="company" value="<?=$form['company'];?>" />
		</li>
		<li>
			<label for="address_1">Street Address</label>
			<input type="text" class="text required" name="address_1" id="address_1" value="<?=$form['address_1'];?>">
		</li>
		<li>
			<label for="address_2">Address Line 2</label>
			<input type="text" class="text" name="address_2" id="address_2" value="<?=$form['address_2'];?>">
		</li>
		<li>
			<label for="city">City</label>
			<input type="text" class="text required" name="city" id="city" value="<?=$form['city'];?>">
		</li>
		<li>
			<label for="state">Region</label>
			<input type="text" class="text" name="state" id="state" value="<?=$form['state'];?>"><select id="state_select" name="state_select"><? foreach ($states as $state) { ?><option <? if ($form['state'] == $state['code']) { ?> selected="selected" <? } ?> value="<?=$state['code'];?>"><?=$state['name'];?></option><? } ?></select>
		</li>
		<li>
			<label for="Country">Country</label>
			<select id="country" name="country" class="required"><? foreach ($countries as $country) { ?><option <? if ($form['country'] == $country['iso2']) { ?> selected="selected" <? } ?> value="<?=$country['iso2'];?>"><?=$country['name'];?></option><? } ?></select>
		</li>
		<li>
			<label for="postal_code">Postal Code</label>
			<input type="text" class="text required" name="postal_code" id="postal_code" value="<?=$form['postal_code'];?>">
		</li>
		<li>
			<label for="phone">Phone</label>
			<input type="text" class="text" id="phone" name="phone" value="<?=$form['phone'];?>" />
		</li>
		<li>
			<label for="timezone">Timezone</label>
			<?=timezone_menu($form['gmt_offset']);?>
		</li>
	</ul>
</fieldset>
<fieldset>
	<legend>Access</legend>
	<ul class="form">
		<li>
			<label for="client_type">Client Type</label>
			<select name="client_type" id="client_type">
				<? if ($this->user->Get('client_type_id') == '3') { ?><option <? if ($form['client_type'] == '1') { ?> selected="selected" <? } ?> value="1">Service Provider</option><? } ?>
				<option <? if ($form['client_type'] == '2') { ?> selected="selected" <? } ?> value="2">End User</option>
			</select>
		</li>
		<? if ($this->user->Get('client_type_id') == '3') { ?>
		<li>
			<div class="help"><b>Service Providers</b> can create client accounts for End Users.  <b>End Users</b> do not have client creation abilities.</span>
		</li>
		<? } ?>
		<li>
			<label for="suspended">Status</label>
			<input type="radio" class="required" id="suspended" name="suspended" <? if ($form['suspended'] == '0') { ?> checked="checked" <? } ?> value="0" />&nbsp;Active&nbsp;&nbsp;&nbsp;<input type="radio" class="required" id="suspended" name="suspended" <? if ($form['suspended'] == '1') { ?> checked="checked" <? } ?> value="1" />&nbsp;Suspended
		</li>
	</ul>
</fieldset>
<div class="submit">
	<input type="submit" name="go_email" value="<?=ucfirst($form_title);?>" />
</div>
</form>
<?=$this->load->view('cp/footer');?>