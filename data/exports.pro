database "F:\mutall_project\development\majorm\majorm.accdb"

query [exports]
	sql
	"SELECT"
		"*"
	"FROM"
		"[export]"
	{
		client_name -> [client].[name];
		client_id_no -> [client].[id_no];
          wconnection_name -> [wconnection].[name];
          phone_num -> [phone].[num];
          0-> client_phone.valid
	}


