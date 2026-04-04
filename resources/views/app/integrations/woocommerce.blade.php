<form method="POST" action="{{ route('app.integrations.woocommerce.store') }}">
    @csrf

    <div>
        <label>Store Name</label>
        <input type="text" name="store_name">
    </div>

    <div>
        <label>Store URL</label>
        <input type="url" name="store_url" placeholder="https://yourstore.com" required>
    </div>

    <div>
        <label>Consumer Key</label>
        <input type="text" name="api_key" required>
    </div>

    <div>
        <label>Consumer Secret</label>
        <input type="text" name="api_secret" required>
    </div>

    <button type="submit">Save Woo Store</button>
</form>