WITH rng AS (
  SELECT
    CASE :period
      WHEN 'D' THEN TRUNC(SYSDATE-1)                -- yesterday
      WHEN 'W' THEN TRUNC(SYSDATE,'IW')-7           -- last week
      WHEN 'M' THEN ADD_MONTHS(TRUNC(SYSDATE,'MM'),-1) -- last month
    END   AS period_start,
    CASE :period
      WHEN 'D' THEN TRUNC(SYSDATE)                  -- today 00:00
      WHEN 'W' THEN TRUNC(SYSDATE,'IW')             -- this week start
      WHEN 'M' THEN TRUNC(SYSDATE,'MM')             -- this month start
    END   AS period_end,
    :shop_id AS shop_id
  FROM dual
)
SELECT NVL(SUM(oi.quantity*oi.unit_price),0) AS sales,
       COUNT(DISTINCT o.order_id)            AS orders,
       CASE WHEN COUNT(DISTINCT o.order_id) = 0
            THEN 0
            ELSE ROUND( SUM(oi.quantity*oi.unit_price)
                      / COUNT(DISTINCT o.order_id), 2) END AS avg_order_value
FROM      rng
LEFT JOIN order1      o  ON o.order_date >= rng.period_start
                        AND o.order_date <  rng.period_end
LEFT JOIN order_item  oi ON oi.order_id  = o.order_id
LEFT JOIN product     p  ON p.product_id = oi.product_id
WHERE     p.shop_id   = rng.shop_id 