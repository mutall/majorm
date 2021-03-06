database "F:\mutall_project\development\majorm\majorm2.accdb"

query [client]
	sql
	"SELECT"
		"[client].[name] AS client_name,"
		"[client].[phone] AS client_phone,"
		"[client].[id_no] AS client_id_no,"
		"[client].[comment] AS client_comment"
	"FROM"
		"[client]"
	{
		client_name -> [client].[name];
		client_phone -> [client].[phone];
		client_id_no -> [client].[id_no];
		client_comment -> [client].[comment]
	}


query [phone]
	sql
	"SELECT"
		"[phone].[num] AS phone_num"
	"FROM"
		"[phone]"
	{
		phone_num -> [phone].[num]
	}


query [reader]
	sql
	"SELECT"
		"[reader].[name] AS reader_name,"
		"[reader].[phone] AS reader_phone"
	"FROM"
		"[reader]"
	{
		reader_name -> [reader].[name];
		reader_phone -> [reader].[phone]
	}


query [service]
	sql
	"SELECT"
		"[service].[name] AS service_name,"
		"[service].[description] AS service_description,"
		"[service].[price] AS service_price,"
		"[service].[auto] AS service_auto"
	"FROM"
		"[service]"
	{
		service_name -> [service].[name];
		service_description -> [service].[description];
		service_price -> [service].[price];
		service_auto -> [service].[auto]
	}


query [adjustment]
	sql
	"SELECT"
		"[adjustment].[date] AS adjustment_date,"
		"[adjustment].[amount] AS adjustment_amount,"
		"[adjustment].[reason] AS adjustment_reason,"
		"[adjustment].[timestamp] AS adjustment_timestamp,"
		"[client].[name] AS client_name"
	"FROM"
		"[adjustment] INNER JOIN "
		"[client] ON [adjustment].[client] = [client].[client]"
	{
		adjustment_date -> [adjustment].[date];
		adjustment_amount -> [adjustment].[amount];
		adjustment_reason -> [adjustment].[reason];
		adjustment_timestamp -> [adjustment].[timestamp];
		client_name -> [client].[name]
	}


query [charge]
	sql
	"SELECT"
		"[charge].[connection] AS charge_connection,"
		"[charge].[date] AS charge_date,"
		"[charge].[timestamp] AS charge_timestamp,"
		"[charge].[amount] AS charge_amount,"
		"[service].[name] AS service_name"
	"FROM"
		"[charge] INNER JOIN "
		"[service] ON [charge].[service] = [service].[service]"
	{
		charge_connection -> [charge].[connection];
		charge_date -> [charge].[date];
		charge_timestamp -> [charge].[timestamp];
		charge_amount -> [charge].[amount];
		service_name -> [service].[name]
	}


query [client_phone]
	sql
	"SELECT"
		"[client_phone].[valid] AS client_phone_valid,"
		"[client].[name] AS client_name,"
		"[phone].[num] AS phone_num"
	"FROM"
		"([client_phone] INNER JOIN "
		"[client] ON [client_phone].[client] = [client].[client]) INNER JOIN "
		"[phone] ON [client_phone].[phone] = [phone].[phone]"
	{
		client_phone_valid -> [client_phone].[valid];
		client_name -> [client].[name];
		phone_num -> [phone].[num]
	}


query [closing_balance]
	sql
	"SELECT"
		"[closing_balance].[date] AS closing_balance_date,"
		"[closing_balance].[amount] AS closing_balance_amount,"
		"[closing_balance].[timestamp] AS closing_balance_timestamp,"
		"[client].[name] AS client_name"
	"FROM"
		"[closing_balance] INNER JOIN "
		"[client] ON [closing_balance].[client] = [client].[client]"
	{
		closing_balance_date -> [closing_balance].[date];
		closing_balance_amount -> [closing_balance].[amount];
		closing_balance_timestamp -> [closing_balance].[timestamp];
		client_name -> [client].[name]
	}


query [payment]
	sql
	"SELECT"
		"[payment].[date] AS payment_date,"
		"[payment].[amount] AS payment_amount,"
		"[payment].[type] AS payment_type,"
		"[payment].[ref] AS payment_ref,"
		"[payment].[description] AS payment_description,"
		"[payment].[timestamp] AS payment_timestamp,"
		"[client].[name] AS client_name"
	"FROM"
		"[payment] INNER JOIN "
		"[client] ON [payment].[client] = [client].[client]"
	{
		payment_date -> [payment].[date];
		payment_amount -> [payment].[amount];
		payment_type -> [payment].[type];
		payment_ref -> [payment].[ref];
		payment_description -> [payment].[description];
		payment_timestamp -> [payment].[timestamp];
		client_name -> [client].[name]
	}


query [wconnection]
	sql
	"SELECT"
		"[wconnection].[name] AS wconnection_name,"
		"[wconnection].[meter_no] AS wconnection_meter_no,"
		"[wconnection].[latitude] AS wconnection_latitude,"
		"[wconnection].[longitude] AS wconnection_longitude,"
		"[client_wconnection].[client],"
		"[client_wconnection].[client_name] AS client_wconnection_client_name"
	"FROM"
		"[wconnection] LEFT JOIN "
		"("
	"SELECT"
		"[client].[client] AS client,"
		"[client].[name] AS client_name"
	"FROM"
		"[client]"
		")  AS [client_wconnection] ON [wconnection].[client] = [client_wconnection].[client]"
	{
		wconnection_name -> [wconnection].[name];
		wconnection_meter_no -> [wconnection].[meter_no];
		wconnection_latitude -> [wconnection].[latitude];
		wconnection_longitude -> [wconnection].[longitude];
{client_wconnection_client_name -> [client].[name]}
	}


query [reading]
	sql
	"SELECT"
		"[reading].[date] AS reading_date,"
		"[reading].[value] AS reading_value,"
		"[reading].[latitude] AS reading_latitude,"
		"[reading].[longitude] AS reading_longitude,"
		"[reading].[timestamp] AS reading_timestamp,"
		"[wconnection].[name] AS wconnection_name,"
		"[reader_reading].[reader],"
		"[reader_reading].[reader_name] AS reader_reading_reader_name"
	"FROM"
		"([reading] INNER JOIN "
		"[wconnection] ON [reading].[wconnection] = [wconnection].[wconnection]) LEFT JOIN "
		"("
	"SELECT"
		"[reader].[reader] AS reader,"
		"[reader].[name] AS reader_name"
	"FROM"
		"[reader]"
		")  AS [reader_reading] ON [reading].[reader] = [reader_reading].[reader]"
	{
		reading_date -> [reading].[date];
		reading_value -> [reading].[value];
		reading_latitude -> [reading].[latitude];
		reading_longitude -> [reading].[longitude];
		reading_timestamp -> [reading].[timestamp];
		wconnection_name -> [wconnection].[name];
{reader_reading_reader_name -> [reader].[name]}
	}


query [subscription]
	sql
	"SELECT"
		"[subscription].[amount] AS subscription_amount,"
		"[wconnection].[name] AS wconnection_name,"
		"[service].[name] AS service_name"
	"FROM"
		"([subscription] INNER JOIN "
		"[wconnection] ON [subscription].[wconnection] = [wconnection].[wconnection]) INNER JOIN "
		"[service] ON [subscription].[service] = [service].[service]"
	{
		subscription_amount -> [subscription].[amount];
		wconnection_name -> [wconnection].[name];
		service_name -> [service].[name]
	}

