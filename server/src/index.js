const express = require('express')
const axios = require('axios')
const cors = require('cors')
const app = express()
const port = 3000
const COINGECKO_API_URL = process.env.COINGECKO_API_URL || 'https://api.coingecko.com/api/v3';
const DEBUG = process.env.APP_DEBUG === 'true' || true;
const { createClient } = require('redis');

const redis = createClient({
  socket: {
    host: process.env.REDIS_HOST || 'redis',
    port: process.env.REDIS_PORT || 6379,
  }
});

function debugMessage(message) {
  if (DEBUG) {
    console.log(message);
  }
}

redis.on('error', err => console.log('Redis Client Error', err));

(async () => {
  await redis.connect().then(() => {
      debugMessage('Connected to Redis');
  }).catch(err => {
      debugMessage('Failed to connect to Redis:'. err);
  })

  app.listen(port, () => {
      console.log(`App running on port ${port}`)
  })
})();

app.use(cors())

async function fetchCoin(id) {

  debugMessage(`Fetching data from coingecko : ${id}`);

  try {
    const response = await axios.get(`${COINGECKO_API_URL}/coins/${id}`, {
        params: {
            x_cg_demo_api_key: process.env.API_KEY
        }
    });

    return response.data;
  } catch (error) {
    throw new Error(`Failed to fetch data for ID ${id}: ${error.message}`);
  }
}

app.get('/price/:id', async (req, res) => {
  const { id } = req.params;

  if (!id) {
    return res.status(400).json({ error: 'ID parameter is required' });
  }

  debugMessage(`Fetching data for : ${id}`);
  
  try {

    debugMessage(`Attempting to fetch data for ${id} from Redis cache`);
    
    const cached = await redis.get(id);
    if (cached) {
      debugMessage(`Cache hit`);
      return res.json(JSON.parse(cached));
    }else {
      debugMessage(`No cache`);
    }

    const data = await fetchCoin(id);

    let response = {
      id: data.id,
      symbol: data.symbol,
      name: data.name,
      current_price: data.market_data.current_price.usd,
    };

    debugMessage(`Setting cache for ${id} in Redis`);
    
    await redis.setEx(id, 60, JSON.stringify(response));
    res.json(response);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});