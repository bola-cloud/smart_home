import mqtt from 'mqtt';
import express from 'express';
import bodyParser from 'body-parser';

const app = express();
const port = 3000;

// Parse incoming JSON requests
app.use(bodyParser.json());

// MQTT Broker Configuration
const brokerUrl = 'mqtt://91.108.102.82'; // Replace with your MQTT broker URL
const options = {
  clientId: `mqtt-js-client-${Math.random().toString(16).substr(2, 8)}`,
  clean: true,
  connectTimeout: 4000,
  username: '',
  password: '',
};

// Connect to the MQTT broker
const client = mqtt.connect(brokerUrl, options);

// Store last messages for topics (in memory)
const lastMessages = {};

// Listen for messages on subscribed topics
client.on('message', (topic, message) => {
  console.log(`Message received on topic ${topic}:`, message.toString());
  lastMessages[topic] = message.toString(); // Store the last message for the topic
});

// API: Publish to a topic
app.post('/publish', (req, res) => {
  const { topic, message, retain } = req.body;
  if (!topic || !message) {
    return res.status(400).json({ error: 'Topic and message are required' });
  }

  // Publish with retain flag
  client.publish(topic, message, { qos: 1, retain: retain || false }, (err) => {
    if (err) {
      console.error('Publish error:', err);
      return res.status(500).json({ error: 'Failed to publish message' });
    }
    res.json({ success: true, topic, message });
  });
});

// API: Get last retained message for a topic
app.get('/last-message', (req, res) => {
  const topic = req.query.topic;

  if (!topic) {
    return res.status(400).json({ error: 'Topic is required' });
  }

  // Check if the topic has a stored message
  if (!lastMessages[topic]) {
    return res.status(404).json({
      error: `No message found for the given topic: ${topic}`,
    });
  }

  return res.json({
    success: true,
    message: lastMessages[topic],
  });
});

// Start server
app.listen(port, () => {
  console.log(`Server listening at http://localhost:${port}`);
});
