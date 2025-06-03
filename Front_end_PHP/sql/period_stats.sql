/* :period can be 'D','W','M'  (Day, iso-Week, Month) */
WITH rng AS (
    /* ⇢ first day (or first second) of the required range */
    SELECT CASE :period
             WHEN 'D' THEN TRUNC(SYSDATE)                    -- today 00:00
             WHEN 'W' THEN TRUNC(SYSDATE,'IW')               -- monday 00:00
             WHEN 'M' THEN TRUNC(SYSDATE,'MM')               -- 01 mm 00:00
           END AS period_start,
           :shop_id            AS shop_id
    FROM   dual
)
SELECT
    -- money & orders
    NVL(SUM(oi.quantity*oi.unit_price),0)                     AS sales,
    COUNT(DISTINCT o.order_id)                                AS orders,
    CASE WHEN COUNT(DISTINCT o.order_id) = 0
         THEN 0
         ELSE ROUND( SUM(oi.quantity*oi.unit_price)
                   / COUNT(DISTINCT o.order_id), 2) END       AS avg_order_value,
    NVL(SUM(oi.quantity),0)                                   AS items_sold,
    -- customers
    COUNT(DISTINCT o.user_id)                                 AS unique_cust,
    -- items / order again (handy card)
    CASE WHEN COUNT(DISTINCT o.order_id)=0
         THEN 0
         ELSE ROUND( SUM(oi.quantity)
                   / COUNT(DISTINCT o.order_id),1) END        AS items_per_order
FROM      rng
LEFT JOIN order1      o  ON o.order_date >= rng.period_start
LEFT JOIN order_item  oi ON oi.order_id  = o.order_id
LEFT JOIN product     p  ON p.product_id = oi.product_id
WHERE     p.shop_id   = rng.shop_id 