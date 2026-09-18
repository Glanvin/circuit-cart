| Column Name  | Data Type     | Nullable | Default             | Description                                                             |
| ------------ | ------------- | -------- | ------------------- | ----------------------------------------------------------------------- |
| id           | int(11)       | No       | None                | Primary Key, Auto-incrementing identifier.                              |
| brand        | varchar(100)  | No       | None                | The manufacturer of the product (e.g., Apple, Sony).                    |
| model_name   | varchar(150)  | No       | None                | The commercial name of the device (e.g., PlayStation 5).                |
| model_code   | varchar(100)  | No       | None                | Unique identifier, model number, or SKU.                                |
| category     | varchar(100)  | No       | None                | Product grouping (e.g., smartphone, laptop, console).                   |
| price        | decimal(10,2) | No       | None                | Base retail price of the full device.                                   |
| model_parts  | longtext      | Yes      | NULL                | JSON payload containing inventory and pricing for the device and parts. |
| is_active    | tinyint(1)    | Yes      | 1                   | Boolean flag (1 = True, 0 = False) indicating if the product is active. |
| date_created | timestamp     | No       | current_timestamp() | Auto-generated timestamp of when the record was inserted.               |


```json
{
  "whole_device": {
    "stock": 45,
    "price": 999.99
  },
  "parts": {
    "screen": {
      "stock": 20,
      "price": 279.00
    },
    "battery": {
      "stock": 50,
      "price": 99.00
    },
    "motherboard": {
      "stock": 10,
      "price": 349.00
    }
  }
}
```