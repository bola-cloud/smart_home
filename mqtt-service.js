import mqtt from 'mqtt';
import express from 'express';
import bodyParser from 'body-parser';

const app = express();
const port = 14000; // Use a different port

// Parse incoming JSON requests
app.use(bodyParser.json());

// MQTT Broker Configuration
const brokerUrl = 'mqtt://91.108.102.82'; // Replace with your MQTT broker URL
const options = {
  clientId: `mqtt-js-client-${Math.random().toString(16).substr(2, 8)}`,
  clean: true,
  connectTimeout: 4000,
  username: '', // Add if required
  password: '', // Add if required
};

// Connect to the MQTT broker
const client = mqtt.connect(brokerUrl, options);

client.on('connect', () => {
  console.log('Connected to MQTT broker');

  // Subscribe to specific topics on startup (if required)
  client.subscribe('Mazaya/11/3', { qos: 1 }, (err) => {
    if (err) {
      console.error('Subscription error:', err);
    } else {
      console.log('Subscribed to topic: Mazaya/11/3');
    }
  });
});

client.on('error', (err) => {
  console.error('MQTT Connection error:', err);
});

// Store last messages for topics (in memory)
const lastMessages = {};

// Listen for messages on subscribed topics
client.on('message', (topic, message) => {
  console.log(`Message received on topic ${topic}:`, message.toString());

  // Parse the message and store it in memory
  try {
    const parsedMessage = JSON.parse(message.toString());
    lastMessages[topic] = parsedMessage; // Store as a JSON object
  } catch (err) {
    console.error('Failed to parse message:', err);
    lastMessages[topic] = message.toString(); // Store raw message if parsing fails
  }
});

// API: Publish to a topic
app.post('/publish', (req, res) => {
  const { topic, message, retain } = req.body;
  if (!topic || !message) {
    return res.status(400).json({ error: 'Topic and message are required' });
  }

  client.publish(topic, message, { qos: 1, retain: retain || false }, (err) => {
    if (err) {
      console.error('Publish error:', err);
      return res.status(500).json({ error: 'Failed to publish message' });
    }
    res.json({ success: true, topic, message });
  });
});

// API: Subscribe to a topic
app.post('/subscribe', (req, res) => {
  const { topic } = req.body;
  if (!topic) {
    return res.status(400).json({ error: 'Topic is required' });
  }

  client.subscribe(topic, { qos: 1 }, (err) => {
    if (err) {
      console.error('Subscribe error:', err);
      return res.status(500).json({ error: 'Failed to subscribe to topic' });
    }
    res.json({ success: true, topic });
  });
});

// API: Get last message for a topic
app.get('/last-message', (req, res) => {
  const { topic } = req.query;
  if (!topic) {
    return res.status(400).json({ error: 'Topic is required' });
  }

  const message = lastMessages[topic];
  if (!message) {
    // Return a valid response even if no message exists
    return res.status(404).json({ success: false, topic, message: null });
  }

  res.json({ success: true, topic, message });
});

// Middleware to log incoming requests
app.use((req, res, next) => {
  console.log(`[${new Date().toISOString()}] ${req.method} ${req.url}`);
  next();
});

// Global error handler for unexpected errors
app.use((err, req, res, next) => {
  console.error('Unexpected error:', err.stack);
  res.status(500).json({ error: 'Internal Server Error' });
});

// Start the server
app.listen(port, () => {
  console.log(`MQTT Service is running at http://localhost:${port}`);
});
