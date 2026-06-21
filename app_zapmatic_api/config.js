var config = {
	debug: false,
	extended_functions: true,
	save_files: true,
	prefix: 'sp8',
	frontend: 'https://zapmatic.tec.br',
	redis: 'redis://:@127.0.0.1:6379',
	port: 9000,
	default_openai_key: '',
	time_to_reset: 120,
	database: {
		connectionLimit: 500,
		host: "localhost",
		user: "db_zapmatic_sql",
		password: "inTwk7z37PnhWcY5",
		database: "db_zapmatic_sql",
		charset: "utf8mb4",
		debug: false,
		waitForConnections: true,
		multipleStatements: true
	},
	cors: {
		origin: '*',
		optionsSuccessStatus: 200
	}
}
module.exports = config; 