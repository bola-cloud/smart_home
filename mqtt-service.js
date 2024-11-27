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
  username: '', // Add if required
  password: '', // Add if required
};

// Connect to the MQTT broker
const client = mqtt.connect(brokerUrl, options);

// Store last messages for topics (in memory)
const lastMessages = {};

// Listen for messages on subscribed topics
client.on('connect', () => {
  console.log('Connected to MQTT broker');
});

// Handle received messages
client.on('message', (topic, message) => {
  console.log(`Message received on topic ${topic}:`, message.toString());
  lastMessages[topic] = message.toString();  // Store the last message for the topic
});

// Log subscription ack (debugging subscriptions)
client.on('suback', (packet) => {
  packet.granted.forEach((qos, index) => {
    const topic = packet.topics[index];
    console.log(`Subscribed to topic: ${topic} with QoS: ${qos}`);
  });
});

// API to Publish message
app.post('/publish', (req, res) => {
  const { topic, message, retain } = req.body;
  if (!topic || !message) {
      return res.status(400).json({ error: 'Topic and message are required' });
  }

  // Publish with retain flag
  client.publish(topic, message, { qos: 1, retain: true }, (err) => {
      if (err) {
          console.error('Publish error:', err);
          return res.status(500).json({ error: 'Failed to publish message' });
      }
      res.json({ success: true, topic, message });
  });
});

// API to Subscribe to a topic
app.post('/subscribe', (req, res) => {
  const { topic } = req.body;
  if (!topic) {
    return res.status(400).json({ error: 'Topic is required' });
  }

  // Subscribe to the topic with QoS 1
  client.subscribe(topic, { qos: 1 }, (err) => {
    if (err) {
      console.error('Subscribe error:', err);
      return res.status(500).json({ error: 'Failed to subscribe to topic' });
    }
    res.json({ success: true, topic });
  });
});

// Route for getting last retained message
app.get('/last-message', (req, res) => {
  const topic = req.query.topic;

  if (!topic || !lastMessages[topic]) {
      return res.status(404).json({
          error: `No message found for the given topic: ${topic}`,
      });
  }

  return res.json({
      success: true,
      message: lastMessages[topic],
  });
});

// Start the Express server
app.listen(port, () => {
  console.log(`Server is running on http://localhost:${port}`);
});
