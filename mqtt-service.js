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

// Subscribe to a specific topic (for example, Mazaya/11/3)
function subscribeToTopic(deviceId, componentOrder) {
  const topic = `Mazaya/${deviceId}/${componentOrder}`;
  client.subscribe(topic, { qos: 1 }, (err) => {
    if (err) {
      console.error('Subscription error:', err);
    } else {
      console.log(`Successfully subscribed to: ${topic}`);
    }
  });
}

// API to Publish message
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

// API to Subscribe to a topic
app.post('/subscribe', (req, res) => {
  const { device_id, component_order } = req.body;

  if (!device_id || !component_order) {
    return res.status(400).json({ error: 'Device ID and Component Order are required' });
  }

  // Subscribe to the topic based on provided device ID and component order
  subscribeToTopic(device_id, component_order);
  
  // Send back the last message received on this topic
  const topic = `Mazaya/${device_id}/${component_order}`;
  const lastMessage = lastMessages[topic] || 'No message received yet.';
  
  res.json({ success: true, topic, lastMessage });
});

// Start the Express server
app.listen(port, () => {
  console.log(`Server is running on http://localhost:${port}`);
});
